<?php

namespace Modules\DoubleEntry\Services;

use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\JournalLine;

class AccountBalanceService
{
    /**
     * Normal balance side per account type.
     * Assets & Expenses have normal debit balances.
     * Liabilities, Equity & Income have normal credit balances.
     */
    public static function normalBalanceSide(string $type): string
    {
        return match ($type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'income' => 'credit',
            default => 'debit',
        };
    }

    /**
     * Get the balance for a single account as of a date.
     * Returns positive for normal balance, negative for contra.
     */
    public function getBalance(int $accountId, ?string $asOfDate = null, string $basis = 'accrual'): float
    {
        $account = Account::find($accountId);
        if (! $account) {
            return 0;
        }

        $query = JournalLine::where('account_id', $accountId)
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journals.company_id', $account->company_id)
            ->where('double_entry_journals.status', 'posted')
            ->where('double_entry_journals.basis', $basis);

        if ($asOfDate) {
            $query->where('double_entry_journals.date', '<=', $asOfDate);
        }

        $totals = $query->select([
            DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
        ])->first();

        $openingBalance = (float) $account->opening_balance;

        if (self::normalBalanceSide($account->type) === 'debit') {
            return $openingBalance + $totals->total_debit - $totals->total_credit;
        }

        return $openingBalance + $totals->total_credit - $totals->total_debit;
    }

    /**
     * Get balances for all accounts grouped by type.
     */
    public function getBalancesByType(int $companyId, ?string $dateFrom = null, ?string $dateTo = null, string $basis = 'accrual'): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
                ->where('double_entry_journals.company_id', $companyId)
                ->where('double_entry_journals.status', 'posted')
                ->where('double_entry_journals.basis', $basis);

            if ($dateFrom) {
                $query->where('double_entry_journals.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('double_entry_journals.date', '<=', $dateTo);
            }

            $totals = $query->select([
                DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
            ])->first();

            $result[$account->type][] = [
                'account' => $account,
                'debit' => (float) $totals->total_debit,
                'credit' => (float) $totals->total_credit,
                'balance' => self::normalBalanceSide($account->type) === 'debit'
                    ? (float) $account->opening_balance + $totals->total_debit - $totals->total_credit
                    : (float) $account->opening_balance + $totals->total_credit - $totals->total_debit,
            ];
        }

        return $result;
    }

    /**
     * Get running balance entries for an account over a period.
     */
    public function getRunningBalance(int $accountId, ?string $dateFrom = null, ?string $dateTo = null, string $basis = 'accrual'): array
    {
        $account = Account::find($accountId);
        if (! $account) {
            return [];
        }

        $isDebitNormal = self::normalBalanceSide($account->type) === 'debit';

        // Calculate opening balance (all entries before dateFrom)
        $openingBalance = (float) $account->opening_balance;

        if ($dateFrom) {
            $priorTotals = JournalLine::where('account_id', $accountId)
                ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
                ->where('double_entry_journals.company_id', $account->company_id)
                ->where('double_entry_journals.status', 'posted')
                ->where('double_entry_journals.basis', $basis)
                ->where('double_entry_journals.date', '<', $dateFrom)
                ->select([
                    DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
                    DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
                ])->first();

            if ($isDebitNormal) {
                $openingBalance += $priorTotals->total_debit - $priorTotals->total_credit;
            } else {
                $openingBalance += $priorTotals->total_credit - $priorTotals->total_debit;
            }
        }

        // Get journal lines in the period
        $query = JournalLine::where('double_entry_journal_lines.account_id', $accountId)
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journals.company_id', $account->company_id)
            ->where('double_entry_journals.status', 'posted')
            ->where('double_entry_journals.basis', $basis)
            ->orderBy('double_entry_journals.date')
            ->orderBy('double_entry_journals.id');

        if ($dateFrom) {
            $query->where('double_entry_journals.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('double_entry_journals.date', '<=', $dateTo);
        }

        $lines = $query->select([
            'double_entry_journal_lines.*',
            'double_entry_journals.date',
            'double_entry_journals.reference',
            'double_entry_journals.description as journal_description',
            'double_entry_journals.id as journal_id',
        ])->get();

        $runningBalance = $openingBalance;
        $entries = [];

        foreach ($lines as $line) {
            if ($isDebitNormal) {
                $runningBalance += $line->debit - $line->credit;
            } else {
                $runningBalance += $line->credit - $line->debit;
            }

            $entries[] = [
                'date' => $line->date,
                'journal_id' => $line->journal_id,
                'reference' => $line->reference,
                'description' => $line->description ?: $line->journal_description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => $account,
            'opening_balance' => $openingBalance,
            'entries' => $entries,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Get trial balance data: each account's total debits, credits, and balance.
     */
    public function getTrialBalance(int $companyId, ?string $dateFrom = null, ?string $dateTo = null, string $basis = 'accrual'): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $result = [];
        $grandDebit = 0;
        $grandCredit = 0;

        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
                ->where('double_entry_journals.company_id', $companyId)
                ->where('double_entry_journals.status', 'posted')
                ->where('double_entry_journals.basis', $basis);

            if ($dateFrom) {
                $query->where('double_entry_journals.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('double_entry_journals.date', '<=', $dateTo);
            }

            $totals = $query->select([
                DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
            ])->first();

            $debit = (float) $totals->total_debit;
            $credit = (float) $totals->total_credit;

            // Include opening balance in trial balance
            $opening = (float) $account->opening_balance;
            if (self::normalBalanceSide($account->type) === 'debit') {
                $debit += $opening;
            } else {
                $credit += $opening;
            }

            // Only include accounts with activity or opening balance
            if ($debit == 0 && $credit == 0) {
                continue;
            }

            // Net to single column
            $netDebit = 0;
            $netCredit = 0;
            if ($debit >= $credit) {
                $netDebit = $debit - $credit;
            } else {
                $netCredit = $credit - $debit;
            }

            $result[$account->type][] = [
                'account' => $account,
                'debit' => $netDebit,
                'credit' => $netCredit,
            ];

            $grandDebit += $netDebit;
            $grandCredit += $netCredit;
        }

        return [
            'accounts' => $result,
            'grand_debit' => $grandDebit,
            'grand_credit' => $grandCredit,
        ];
    }
}
