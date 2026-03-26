<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransferCreated as Event;
use Illuminate\Support\Facades\Log;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;

class TransferCreated
{
    public function handle(Event $event): void
    {
        $transfer = $event->transfer;
        $companyId = $transfer->company_id;

        $sourceBankDefault = AccountDefault::where('company_id', $companyId)->type('bank')->first();
        $destBankDefault = AccountDefault::where('company_id', $companyId)->type('bank_destination')->first()
            ?? $sourceBankDefault;

        if (! $sourceBankDefault) {
            Log::warning('DoubleEntry: Missing default bank account for transfer auto-posting', [
                'company_id' => $companyId,
                'transfer_id' => $transfer->id,
            ]);

            return;
        }

        $amount = $transfer->expense_transaction->amount ?? $transfer->amount ?? 0;

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $transfer->transferred_at ?? $transfer->created_at,
            'reference' => 'TRF-' . $transfer->id,
            'description' => 'Auto-posted from Transfer #' . $transfer->id,
            'basis' => 'cash',
            'status' => 'posted',
            'documentable_type' => get_class($transfer),
            'documentable_id' => $transfer->id,
        ]);

        // DR: Destination bank account
        $journal->lines()->create([
            'account_id' => $destBankDefault->account_id,
            'debit' => $amount,
            'credit' => 0,
            'description' => 'Transfer In',
        ]);

        // CR: Source bank account
        $journal->lines()->create([
            'account_id' => $sourceBankDefault->account_id,
            'debit' => 0,
            'credit' => $amount,
            'description' => 'Transfer Out',
        ]);
    }
}
