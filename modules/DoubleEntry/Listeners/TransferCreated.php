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
        $amount = (float) ($transfer->amount ?? $transfer->expense_transaction->amount ?? 0);

        $bankDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'bank_current')->first();

        if (! $bankDefault || $amount <= 0) {
            return;
        }

        $journal = Journal::firstOrCreate([
            'company_id' => $companyId,
            'reference' => 'transfer:' . $transfer->id,
        ], [
            'number' => 'JE-XFR-' . strtoupper(substr(md5($transfer->id), 0, 8)),
            'date' => $transfer->transferred_at ?? $transfer->created_at,
            'description' => 'Transfer #' . $transfer->id,
            'status' => 'posted',
        ]);

        if ($journal->lines()->exists()) {
            return;
        }

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $bankDefault->account_id,
            'debit' => $amount,
            'credit' => 0,
            'description' => 'Transfer in',
        ]);

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $bankDefault->account_id,
            'debit' => 0,
            'credit' => $amount,
            'description' => 'Transfer out',
        ]);
    }
}
