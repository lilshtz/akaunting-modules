<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentCreated as Event;
use Illuminate\Support\Facades\Log;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;

class DocumentCreated
{
    public function handle(Event $event): void
    {
        $document = $event->document;
        $companyId = $document->company_id;

        if ($document->type === 'invoice') {
            $this->postInvoiceJournal($document, $companyId);
        } elseif ($document->type === 'bill') {
            $this->postBillJournal($document, $companyId);
        }
    }

    protected function postInvoiceJournal($invoice, int $companyId): void
    {
        $arDefault = AccountDefault::where('company_id', $companyId)->type('accounts_receivable')->first();
        $revenueDefault = AccountDefault::where('company_id', $companyId)->type('sales_revenue')->first();

        if (! $arDefault || ! $revenueDefault) {
            Log::warning('DoubleEntry: Missing default accounts for invoice auto-posting', [
                'company_id' => $companyId,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        $taxAmount = 0;
        if (isset($invoice->tax_total)) {
            $taxAmount = (float) $invoice->tax_total;
        }

        $revenueAmount = (float) $invoice->amount - $taxAmount;

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $invoice->issued_at ?? $invoice->created_at,
            'reference' => 'INV-' . $invoice->document_number,
            'description' => 'Auto-posted from Invoice ' . $invoice->document_number,
            'basis' => 'accrual',
            'status' => 'posted',
            'documentable_type' => get_class($invoice),
            'documentable_id' => $invoice->id,
            'created_by' => $invoice->created_by ?? null,
        ]);

        // DR: Accounts Receivable
        $journal->lines()->create([
            'account_id' => $arDefault->account_id,
            'debit' => $invoice->amount,
            'credit' => 0,
            'description' => 'Accounts Receivable',
        ]);

        // CR: Sales Revenue
        $journal->lines()->create([
            'account_id' => $revenueDefault->account_id,
            'debit' => 0,
            'credit' => $revenueAmount,
            'description' => 'Sales Revenue',
        ]);

        // CR: Tax Payable (if tax exists)
        if ($taxAmount > 0) {
            $taxDefault = AccountDefault::where('company_id', $companyId)->type('tax_payable')->first();

            if ($taxDefault) {
                $journal->lines()->create([
                    'account_id' => $taxDefault->account_id,
                    'debit' => 0,
                    'credit' => $taxAmount,
                    'description' => 'Tax Payable',
                ]);
            } else {
                // If no tax account, put full amount to revenue
                $journal->lines()->where('description', 'Sales Revenue')->first()
                    ?->update(['credit' => $invoice->amount]);
            }
        }
    }

    protected function postBillJournal($bill, int $companyId): void
    {
        $apDefault = AccountDefault::where('company_id', $companyId)->type('accounts_payable')->first();
        $expenseDefault = AccountDefault::where('company_id', $companyId)->type('expense')->first();

        if (! $apDefault || ! $expenseDefault) {
            Log::warning('DoubleEntry: Missing default accounts for bill auto-posting', [
                'company_id' => $companyId,
                'bill_id' => $bill->id,
            ]);

            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $bill->issued_at ?? $bill->created_at,
            'reference' => 'BILL-' . $bill->document_number,
            'description' => 'Auto-posted from Bill ' . $bill->document_number,
            'basis' => 'accrual',
            'status' => 'posted',
            'documentable_type' => get_class($bill),
            'documentable_id' => $bill->id,
            'created_by' => $bill->created_by ?? null,
        ]);

        // DR: Expense
        $journal->lines()->create([
            'account_id' => $expenseDefault->account_id,
            'debit' => $bill->amount,
            'credit' => 0,
            'description' => 'Expense',
        ]);

        // CR: Accounts Payable
        $journal->lines()->create([
            'account_id' => $apDefault->account_id,
            'debit' => 0,
            'credit' => $bill->amount,
            'description' => 'Accounts Payable',
        ]);
    }
}
