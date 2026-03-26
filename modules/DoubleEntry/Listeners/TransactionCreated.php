<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransactionCreated as Event;
use Illuminate\Support\Facades\Log;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;

class TransactionCreated
{
    public function handle(Event $event): void
    {
        $transaction = $event->transaction;
        $companyId = $transaction->company_id;

        if ($transaction->type === 'income') {
            $this->postPaymentReceived($transaction, $companyId);
        } elseif ($transaction->type === 'expense') {
            $this->postPaymentMade($transaction, $companyId);
        }
    }

    protected function postPaymentReceived($transaction, int $companyId): void
    {
        $bankDefault = AccountDefault::where('company_id', $companyId)->type('bank')->first();
        $arDefault = AccountDefault::where('company_id', $companyId)->type('accounts_receivable')->first();

        if (! $bankDefault || ! $arDefault) {
            Log::warning('DoubleEntry: Missing default accounts for payment received auto-posting', [
                'company_id' => $companyId,
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $transaction->paid_at ?? $transaction->created_at,
            'reference' => 'PMT-' . ($transaction->number ?? $transaction->id),
            'description' => 'Auto-posted from Payment Received #' . ($transaction->number ?? $transaction->id),
            'basis' => 'cash',
            'status' => 'posted',
            'documentable_type' => get_class($transaction),
            'documentable_id' => $transaction->id,
            'created_by' => $transaction->created_by ?? null,
        ]);

        // DR: Bank/Cash
        $journal->lines()->create([
            'account_id' => $bankDefault->account_id,
            'debit' => $transaction->amount,
            'credit' => 0,
            'description' => 'Bank/Cash Receipt',
        ]);

        // CR: Accounts Receivable
        $journal->lines()->create([
            'account_id' => $arDefault->account_id,
            'debit' => 0,
            'credit' => $transaction->amount,
            'description' => 'Accounts Receivable',
        ]);
    }

    protected function postPaymentMade($transaction, int $companyId): void
    {
        $apDefault = AccountDefault::where('company_id', $companyId)->type('accounts_payable')->first();
        $bankDefault = AccountDefault::where('company_id', $companyId)->type('bank')->first();

        if (! $apDefault || ! $bankDefault) {
            Log::warning('DoubleEntry: Missing default accounts for payment made auto-posting', [
                'company_id' => $companyId,
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $transaction->paid_at ?? $transaction->created_at,
            'reference' => 'PAY-' . ($transaction->number ?? $transaction->id),
            'description' => 'Auto-posted from Payment Made #' . ($transaction->number ?? $transaction->id),
            'basis' => 'cash',
            'status' => 'posted',
            'documentable_type' => get_class($transaction),
            'documentable_id' => $transaction->id,
            'created_by' => $transaction->created_by ?? null,
        ]);

        // DR: Accounts Payable
        $journal->lines()->create([
            'account_id' => $apDefault->account_id,
            'debit' => $transaction->amount,
            'credit' => 0,
            'description' => 'Accounts Payable',
        ]);

        // CR: Bank/Cash
        $journal->lines()->create([
            'account_id' => $bankDefault->account_id,
            'debit' => 0,
            'credit' => $transaction->amount,
            'description' => 'Bank/Cash Payment',
        ]);
    }
}
