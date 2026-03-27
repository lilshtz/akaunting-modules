<?php

namespace Modules\DoubleEntry\Services;

use Illuminate\Support\Collection;
use Modules\DoubleEntry\Models\JournalLine;

class AccountBalanceService
{
    /**
     * Get the general ledger: journal lines per account with running balances.
     */
    public function getGeneralLedger(Collection $accounts, string $startDate, string $endDate): array
    {
        $ledger = [];

        foreach ($accounts as $account) {
            $openingBalance = $this->getAccountBalance($account, now()->parse($startDate)->subDay()->toDateString());
            $lines = JournalLine::where('account_id', $account->id)
                ->where('company_id', $account->company_id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->with('journal')
                ->get()
                ->sortBy('journal.date');

            if ($lines->isEmpty()) {
                continue;
            }

            $isDebitNormal = in_array($account->type, ['asset', 'expense']);
            $runningBalance = $openingBalance;
            $entries = [];

            foreach ($lines as $line) {
                if ($isDebitNormal) {
                    $runningBalance += $line->debit - $line->credit;
                } else {
                    $runningBalance += $line->credit - $line->debit;
                }

                $entries[] = [
                    'date' => $line->journal->date,
                    'number' => $line->journal->number,
                    'description' => $line->description ?? $line->journal->description,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'balance' => $runningBalance,
                ];
            }

            $ledger[] = [
                'account' => $account,
                'opening_balance' => $openingBalance,
                'entries' => $entries,
                'total_debit' => $lines->sum('debit'),
                'total_credit' => $lines->sum('credit'),
                'closing_balance' => $runningBalance,
            ];
        }

        return $ledger;
    }

    /**
     * Get trial balance: debit/credit totals per account.
     */
    public function getTrialBalance(Collection $accounts, string $startDate, string $endDate): array
    {
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $debits = JournalLine::where('account_id', $account->id)
                ->where('company_id', $account->company_id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->sum('debit');

            $credits = JournalLine::where('account_id', $account->id)
                ->where('company_id', $account->company_id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->sum('credit');

            $isDebitNormal = in_array($account->type, ['asset', 'expense']);
            $balance = $isDebitNormal ? ($debits - $credits) : ($credits - $debits);
            $balance += ($account->opening_balance ?? 0);

            $debitBalance = $balance > 0 ? $balance : 0;
            $creditBalance = $balance < 0 ? abs($balance) : 0;

            if ($debitBalance == 0 && $creditBalance == 0) {
                continue;
            }

            $totalDebit += $debitBalance;
            $totalCredit += $creditBalance;

            $rows[] = [
                'account' => $account,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * Get balance sheet: assets = liabilities + equity.
     */
    public function getBalanceSheet(Collection $accounts, string $endDate): array
    {
        $sections = [
            'asset' => ['label' => 'Assets', 'accounts' => [], 'total' => 0],
            'liability' => ['label' => 'Liabilities', 'accounts' => [], 'total' => 0],
            'equity' => ['label' => 'Equity', 'accounts' => [], 'total' => 0],
        ];

        foreach ($accounts as $account) {
            if (!isset($sections[$account->type])) {
                continue;
            }

            $balance = $this->getAccountBalance($account, $endDate);

            if ($balance == 0) {
                continue;
            }

            $sections[$account->type]['accounts'][] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $sections[$account->type]['total'] += $balance;
        }

        return $sections;
    }

    /**
     * Get profit & loss: income - expenses = net profit.
     */
    public function getProfitLoss(Collection $accounts, string $startDate, string $endDate): array
    {
        $sections = [
            'income' => ['label' => 'Income', 'accounts' => [], 'total' => 0],
            'expense' => ['label' => 'Expenses', 'accounts' => [], 'total' => 0],
        ];

        foreach ($accounts as $account) {
            if (!isset($sections[$account->type])) {
                continue;
            }

            $debits = JournalLine::where('account_id', $account->id)
                ->where('company_id', $account->company_id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->sum('debit');

            $credits = JournalLine::where('account_id', $account->id)
                ->where('company_id', $account->company_id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->sum('credit');

            $balance = $account->type === 'income'
                ? ($credits - $debits)
                : ($debits - $credits);

            if ($balance == 0) {
                continue;
            }

            $sections[$account->type]['accounts'][] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $sections[$account->type]['total'] += $balance;
        }

        $sections['net_profit'] = $sections['income']['total'] - $sections['expense']['total'];

        return $sections;
    }

    /**
     * Get balance for a single account up to a date.
     */
    protected function getAccountBalance($account, string $endDate): float
    {
        $debits = JournalLine::where('account_id', $account->id)
            ->where('company_id', $account->company_id)
            ->whereHas('journal', function ($q) use ($endDate) {
                $q->where('status', 'posted')
                    ->where('date', '<=', $endDate);
            })
            ->sum('debit');

        $credits = JournalLine::where('account_id', $account->id)
            ->where('company_id', $account->company_id)
            ->whereHas('journal', function ($q) use ($endDate) {
                $q->where('status', 'posted')
                    ->where('date', '<=', $endDate);
            })
            ->sum('credit');

        $isDebitNormal = in_array($account->type, ['asset', 'expense']);

        if ($isDebitNormal) {
            return ($debits - $credits) + ($account->opening_balance ?? 0);
        }

        return ($credits - $debits) + ($account->opening_balance ?? 0);
    }
}
