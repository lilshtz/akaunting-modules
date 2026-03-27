<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransferCreated as Event;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class TransferCreated
{
    /**
     * Handle the event.
     *
     * Auto-post journal entries when transfers between accounts occur.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $transfer = $event->transfer;

        $companyId = $transfer->company_id;

        $bankDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'bank_current')->first();

        if (!$bankDefault) {
            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'number' => 'JE-XFR-' . strtoupper(substr(md5($transfer->id), 0, 8)),
            'date' => $transfer->transferred_at ?? $transfer->created_at,
            'description' => 'Transfer #' . $transfer->id,
            'reference' => 'transfer:' . $transfer->id,
            'status' => 'posted',
        ]);

        // Debit destination bank, credit source bank
        // Using the same bank default for simplicity — real implementation
        // would map Akaunting bank accounts to COA accounts
        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $bankDefault->account_id,
            'debit' => $transfer->expense_transaction->amount ?? 0,
            'credit' => 0,
            'description' => 'Transfer in',
        ]);

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $bankDefault->account_id,
            'debit' => 0,
            'credit' => $transfer->expense_transaction->amount ?? 0,
            'description' => 'Transfer out',
        ]);
    }
}
