<?php

namespace Modules\PaypalSync\Services;

use App\Models\Banking\Transaction;
use App\Models\Common\Contact;
use App\Models\Setting\Category;
use Carbon\Carbon;
use Modules\PaypalSync\Models\PaypalSyncSettings;
use Modules\PaypalSync\Models\PaypalSyncTransaction;

class TransactionSyncService
{
    /**
     * @var PaypalService
     */
    protected $paypalService;

    /**
     * @var PaypalSyncSettings
     */
    protected $settings;

    /**
     * Create a new TransactionSyncService instance.
     *
     * @param PaypalService $paypalService
     * @param PaypalSyncSettings $settings
     */
    public function __construct(PaypalService $paypalService, PaypalSyncSettings $settings)
    {
        $this->paypalService = $paypalService;
        $this->settings = $settings;
    }

    /**
     * Sync PayPal transactions into Akaunting.
     *
     * @param string|null $startDate ISO 8601 start date
     * @param string|null $endDate ISO 8601 end date
     * @return array Results with imported, skipped, and errors counts
     */
    public function sync(?string $startDate = null, ?string $endDate = null): array
    {
        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $endDate = $endDate ?: Carbon::now()->toIso8601String();

        if ($startDate) {
            $start = $startDate;
        } elseif ($this->settings->last_sync) {
            $start = $this->settings->last_sync->toIso8601String();
        } else {
            $start = Carbon::now()->subDays(30)->toIso8601String();
        }

        $page = 1;
        $totalPages = 1;

        while ($page <= $totalPages) {
            try {
                $response = $this->paypalService->fetchTransactions($start, $endDate, $page, 100);
            } catch (\Exception $e) {
                $result['errors']++;
                break;
            }

            $totalPages = $response['total_pages'] ?? 1;
            $transactions = $response['transaction_details'] ?? [];

            foreach ($transactions as $txDetail) {
                try {
                    $txInfo = $txDetail['transaction_info'] ?? [];
                    $payerInfo = $txDetail['payer_info'] ?? [];

                    $transactionId = $txInfo['transaction_id'] ?? null;

                    if (!$transactionId) {
                        $result['errors']++;
                        continue;
                    }

                    // Skip if already imported
                    $exists = PaypalSyncTransaction::where('paypal_transaction_id', $transactionId)->exists();

                    if ($exists) {
                        $result['skipped']++;
                        continue;
                    }

                    $amount = (float) ($txInfo['transaction_amount']['value'] ?? 0);
                    $currency = $txInfo['transaction_amount']['currency_code'] ?? 'USD';
                    $date = isset($txInfo['transaction_initiation_date'])
                        ? Carbon::parse($txInfo['transaction_initiation_date'])->format('Y-m-d')
                        : Carbon::now()->format('Y-m-d');

                    $description = $txInfo['transaction_subject'] ?? $txInfo['transaction_note'] ?? null;
                    $payerEmail = $payerInfo['email_address'] ?? null;
                    $status = $this->mapTransactionStatus($txInfo['transaction_status'] ?? 'S');

                    // Create PayPal sync transaction record
                    $paypalTx = PaypalSyncTransaction::create([
                        'paypal_transaction_id' => $transactionId,
                        'company_id' => $this->settings->company_id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'date' => $date,
                        'description' => $description,
                        'payer_email' => $payerEmail,
                        'status' => $status,
                        'raw_json' => $txDetail,
                    ]);

                    // Create corresponding Akaunting bank transaction
                    if ($status === 'completed' && $this->settings->bank_account_id) {
                        $this->createBankTransaction($paypalTx, $this->settings);
                    }

                    $result['imported']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                }
            }

            $page++;
        }

        // Update last sync timestamp
        $this->settings->update(['last_sync' => Carbon::now()]);

        return $result;
    }

    /**
     * Create an Akaunting bank transaction from a PayPal transaction.
     *
     * @param PaypalSyncTransaction $paypalTx
     * @param PaypalSyncSettings $settings
     * @return Transaction|null
     */
    public function createBankTransaction(PaypalSyncTransaction $paypalTx, PaypalSyncSettings $settings): ?Transaction
    {
        $companyId = $settings->company_id;
        $amount = $paypalTx->amount;
        $type = $amount >= 0 ? 'income' : 'expense';
        $absAmount = abs($amount);

        // Find default category for the transaction type
        $category = Category::where('company_id', $companyId)
            ->where('type', $type)
            ->enabled()
            ->first();

        if (!$category) {
            return null;
        }

        // Try to match contact by payer email
        $contactId = null;
        if ($paypalTx->payer_email) {
            $contact = Contact::where('company_id', $companyId)
                ->where('email', $paypalTx->payer_email)
                ->first();

            if ($contact) {
                $contactId = $contact->id;
            }
        }

        $description = $paypalTx->description ?: 'PayPal transaction';
        if ($paypalTx->payer_email) {
            $description .= ' - ' . $paypalTx->payer_email;
        }

        $bankTransaction = Transaction::create([
            'company_id' => $companyId,
            'type' => $type,
            'account_id' => $settings->bank_account_id,
            'paid_at' => $paypalTx->date->format('Y-m-d'),
            'amount' => $absAmount,
            'currency_code' => $paypalTx->currency,
            'currency_rate' => 1,
            'description' => $description,
            'payment_method' => 'paypal-sync.paypal.1',
            'category_id' => $category->id,
            'contact_id' => $contactId,
            'created_from' => 'paypal-sync::sync',
        ]);

        // Link the bank transaction to the PayPal transaction
        $paypalTx->update(['bank_transaction_id' => $bankTransaction->id]);

        return $bankTransaction;
    }

    /**
     * Attempt to match unmatched PayPal transactions to invoices.
     *
     * @return int Number of matches made
     */
    public function matchToInvoices(): int
    {
        $matched = 0;
        $companyId = $this->settings->company_id;

        $unmatchedTxs = PaypalSyncTransaction::where('company_id', $companyId)
            ->whereNotNull('bank_transaction_id')
            ->where('status', 'completed')
            ->whereNotNull('payer_email')
            ->get();

        foreach ($unmatchedTxs as $paypalTx) {
            $bankTx = $paypalTx->bankTransaction;

            if (!$bankTx || $bankTx->document_id) {
                continue;
            }

            // Find matching invoice by amount and contact email
            $invoice = \App\Models\Document\Document::where('company_id', $companyId)
                ->where('type', 'invoice')
                ->where('amount', abs($paypalTx->amount))
                ->whereHas('contact', function ($query) use ($paypalTx) {
                    $query->where('email', $paypalTx->payer_email);
                })
                ->where('status', '!=', 'paid')
                ->first();

            if ($invoice) {
                $bankTx->update(['document_id' => $invoice->id]);
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Map PayPal transaction status code to our status enum.
     *
     * @param string $statusCode PayPal status code (S, P, V, F, D)
     * @return string
     */
    protected function mapTransactionStatus(string $statusCode): string
    {
        $map = [
            'S' => 'completed',
            'P' => 'pending',
            'V' => 'reversed',
            'F' => 'refunded',
            'D' => 'denied',
        ];

        return $map[$statusCode] ?? 'pending';
    }
}
