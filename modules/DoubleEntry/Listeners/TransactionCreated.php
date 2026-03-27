<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransactionCreated as Event;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class TransactionCreated
{
    /**
     * Handle the event.
     *
     * Auto-post journal entries when payments (income/expense) are recorded.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $transaction = $event->transaction;

        // Skip transfer transactions — handled by TransferCreated
        if (str_contains($transaction->type, 'transfer')) {
            return;
        }

        $this->createJournalFromTransaction($transaction);
    }

    protected function createJournalFromTransaction($transaction)
    {
        $companyId = $transaction->company_id;
        $isIncome = str_contains($transaction->type, 'income');

        // Get bank account default
        $bankDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'bank_current')->first();

        if (!$bankDefault) {
            return;
        }

        if ($isIncome) {
            $receivableDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'accounts_receivable')->first();

            if (!$receivableDefault) {
                return;
            }

            $debitAccountId = $bankDefault->account_id;
            $creditAccountId = $receivableDefault->account_id;
        } else {
            $payableDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'accounts_payable')->first();

            if (!$payableDefault) {
                return;
            }

            $debitAccountId = $payableDefault->account_id;
            $creditAccountId = $bankDefault->account_id;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'number' => 'JE-TXN-' . strtoupper(substr(md5($transaction->id . $transaction->type), 0, 8)),
            'date' => $transaction->paid_at ?? $transaction->created_at,
            'description' => ($isIncome ? 'Income' : 'Expense') . ' payment #' . $transaction->number,
            'reference' => 'transaction:' . $transaction->id,
            'status' => 'posted',
        ]);

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $debitAccountId,
            'debit' => $transaction->amount,
            'credit' => 0,
            'description' => $journal->description,
        ]);

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $transaction->amount,
            'description' => $journal->description,
        ]);
    }
}
