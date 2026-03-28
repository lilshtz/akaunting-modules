<?php

namespace Modules\DoubleEntry\Services;

use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;

class AccountBalanceService
{
    public function getBalance(int $accountId, ?string $asOfDate = null): float
    {
        $account = Account::allCompanies()->find($accountId);

        if (! $account) {
            return 0.0;
        }

        $totals = DB::table('double_entry_journal_lines')
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journal_lines.account_id', $accountId)
            ->where('double_entry_journals.status', 'posted')
            ->whereNull('double_entry_journals.deleted_at')
            ->when($asOfDate, fn ($query) => $query->whereDate('double_entry_journals.date', '<=', $asOfDate))
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as credit_total')
            ->first();

        $movement = self::normalBalanceSide($account->type) === 'debit'
            ? (float) $totals->debit_total - (float) $totals->credit_total
            : (float) $totals->credit_total - (float) $totals->debit_total;

        return round((float) $account->opening_balance + $movement, 4);
    }

    public function getTrialBalance(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('enabled', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $results = [
            'accounts' => [],
            'grand_debit' => 0.0,
            'grand_credit' => 0.0,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        foreach ($accounts as $account) {
            $balance = $this->getBalance($account->id, $dateTo);
            [$debit, $credit] = $this->presentTrialBalanceAmount($account->type, $balance);

            if (round($debit, 4) == 0.0 && round($credit, 4) == 0.0) {
                continue;
            }

            $results['accounts'][$account->type][] = [
                'account' => $account,
                'balance' => round($balance, 4),
                'debit' => round($debit, 4),
                'credit' => round($credit, 4),
            ];

            $results['grand_debit'] += $debit;
            $results['grand_credit'] += $credit;
        }

        $results['grand_debit'] = round($results['grand_debit'], 4);
        $results['grand_credit'] = round($results['grand_credit'], 4);

        return $results;
    }

    public static function normalBalanceSide(string $type): string
    {
        return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    protected function presentTrialBalanceAmount(string $type, float $balance): array
    {
        if (self::normalBalanceSide($type) === 'debit') {
            return $balance >= 0
                ? [$balance, 0.0]
                : [0.0, abs($balance)];
        }

        return $balance >= 0
            ? [0.0, $balance]
            : [abs($balance), 0.0];
    }
}
