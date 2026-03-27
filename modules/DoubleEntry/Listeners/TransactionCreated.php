<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransactionCreated as Event;
use App\Models\Banking\Transaction;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TransactionCreated
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function handle(Event $event): void
    {
        $transaction = $event->transaction;
        $defaults = $this->service->defaultMappings();
        $amount = round((float) ($transaction->amount ?? 0), 4);

        if ($amount <= 0 || !isset($defaults['bank'])) {
            return;
        }

        if (in_array($transaction->type, [Transaction::INCOME_TYPE, Transaction::INCOME_TRANSFER_TYPE], true)) {
            $credit = $transaction->document_id && isset($defaults['accounts_receivable']) ? $defaults['accounts_receivable']->account_id : ($defaults['sales']->account_id ?? null);

            if ($credit) {
                $this->service->upsertAutoJournal('transaction', (int) $transaction->id, [
                    'date' => optional($transaction->paid_at)->format('Y-m-d') ?: now()->toDateString(),
                    'reference' => $transaction->number,
                    'description' => 'Auto-posted income transaction',
                ], [
                    ['account_id' => $defaults['bank']->account_id, 'entry_type' => 'debit', 'amount' => $amount, 'description' => $transaction->description],
                    ['account_id' => $credit, 'entry_type' => 'credit', 'amount' => $amount, 'description' => $transaction->description],
                ]);
            }
        }

        if (in_array($transaction->type, [Transaction::EXPENSE_TYPE, Transaction::EXPENSE_TRANSFER_TYPE], true)) {
            $debit = $transaction->document_id && isset($defaults['accounts_payable']) ? $defaults['accounts_payable']->account_id : ($defaults['purchases']->account_id ?? null);

            if ($debit) {
                $this->service->upsertAutoJournal('transaction', (int) $transaction->id, [
                    'date' => optional($transaction->paid_at)->format('Y-m-d') ?: now()->toDateString(),
                    'reference' => $transaction->number,
                    'description' => 'Auto-posted expense transaction',
                ], [
                    ['account_id' => $debit, 'entry_type' => 'debit', 'amount' => $amount, 'description' => $transaction->description],
                    ['account_id' => $defaults['bank']->account_id, 'entry_type' => 'credit', 'amount' => $amount, 'description' => $transaction->description],
                ]);
            }
        }
    }
}
