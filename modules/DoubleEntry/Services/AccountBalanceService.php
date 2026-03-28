<?php

namespace Modules\DoubleEntry\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;

class AccountBalanceService
{
    public function getAccountBalance(int $accountId, ?string $startDate = null, ?string $endDate = null): float
    {
        $account = Account::query()
            ->byCompany()
            ->find($accountId);

        if (! $account) {
            return 0.0;
        }

        $totals = $this->baseJournalLineTotals()
            ->where('double_entry_journal_lines.account_id', $accountId)
            ->when($startDate, fn ($query) => $query->whereDate('double_entry_journals.date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('double_entry_journals.date', '<=', $endDate))
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as debit_total, COALESCE(SUM(double_entry_journal_lines.credit), 0) as credit_total')
            ->first();

        return round((float) $account->opening_balance + $this->calculateMovement($account->type, $totals), 4);
    }

    public function getBalance(int $accountId, ?string $asOfDate = null): float
    {
        return $this->getAccountBalance($accountId, null, $asOfDate);
    }

    public function getAccountBalances(?string $type = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $totals = $this->baseJournalLineTotals()
            ->when($startDate, fn ($query) => $query->whereDate('double_entry_journals.date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('double_entry_journals.date', '<=', $endDate))
            ->groupBy('double_entry_journal_lines.account_id')
            ->selectRaw('double_entry_journal_lines.account_id, COALESCE(SUM(double_entry_journal_lines.debit), 0) as debit_total, COALESCE(SUM(double_entry_journal_lines.credit), 0) as credit_total')
            ->get()
            ->keyBy('account_id');

        return Account::query()
            ->byCompany()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->where('enabled', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals) {
                $summary = $totals->get($account->id);
                $debitTotal = (float) ($summary->debit_total ?? 0);
                $creditTotal = (float) ($summary->credit_total ?? 0);
                $balance = round((float) $account->opening_balance + $this->calculateMovement($account->type, $summary), 4);

                return [
                    'account' => $account,
                    'debit_total' => round($debitTotal, 4),
                    'credit_total' => round($creditTotal, 4),
                    'balance' => $balance,
                ];
            });
    }

    public function getTrialBalance(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = $this->getAccountBalances(null, $startDate, $endDate);

        $results = [
            'accounts' => [
                'asset' => [],
                'liability' => [],
                'equity' => [],
                'income' => [],
                'expense' => [],
            ],
            'grand_debit' => 0.0,
            'grand_credit' => 0.0,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        foreach ($accounts as $row) {
            $account = $row['account'];
            [$debit, $credit] = $this->presentTrialBalanceAmount($account->type, (float) $row['balance']);

            if (round($debit, 4) === 0.0 && round($credit, 4) === 0.0) {
                continue;
            }

            $results['accounts'][$account->type][] = [
                'account' => $account,
                'balance' => (float) $row['balance'],
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

    public function getBalanceSheet(string $asOfDate): array
    {
        $assets = $this->formatBalanceSection($this->getAccountBalances('asset', null, $asOfDate));
        $liabilities = $this->formatBalanceSection($this->getAccountBalances('liability', null, $asOfDate));
        $equity = $this->formatBalanceSection($this->getAccountBalances('equity', null, $asOfDate));
        $profitLoss = $this->getProfitAndLoss(null, $asOfDate);

        $retainedEarnings = [
            'account' => null,
            'label' => 'Retained Earnings',
            'balance' => round((float) $profitLoss['net_profit'], 4),
        ];

        if (round($retainedEarnings['balance'], 4) !== 0.0) {
            $equity['accounts'][] = $retainedEarnings;
            $equity['total'] = round($equity['total'] + $retainedEarnings['balance'], 4);
        }

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets['total'],
            'total_liabilities_equity' => round($liabilities['total'] + $equity['total'], 4),
        ];
    }

    public function getProfitAndLoss(?string $startDate = null, ?string $endDate = null): array
    {
        $income = $this->formatBalanceSection($this->getAccountBalances('income', $startDate, $endDate));
        $expenses = $this->formatBalanceSection($this->getAccountBalances('expense', $startDate, $endDate));

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income' => $income,
            'expenses' => $expenses,
            'net_profit' => round($income['total'] - $expenses['total'], 4),
        ];
    }

    public static function normalBalanceSide(string $type): string
    {
        return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    protected function baseJournalLineTotals()
    {
        return DB::table('double_entry_journal_lines')
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journals.company_id', company_id())
            ->where('double_entry_journals.status', 'posted')
            ->whereNull('double_entry_journals.deleted_at');
    }

    protected function calculateMovement(string $type, $totals): float
    {
        $debitTotal = (float) ($totals->debit_total ?? 0);
        $creditTotal = (float) ($totals->credit_total ?? 0);

        return self::normalBalanceSide($type) === 'debit'
            ? $debitTotal - $creditTotal
            : $creditTotal - $debitTotal;
    }

    protected function formatBalanceSection(Collection $accounts): array
    {
        $rows = $accounts
            ->map(function (array $row): array {
                /** @var \Modules\DoubleEntry\Models\Account $account */
                $account = $row['account'];

                return [
                    'account' => $account,
                    'label' => $account->code . ' - ' . $account->name,
                    'balance' => round((float) $row['balance'], 4),
                ];
            })
            ->filter(fn (array $row): bool => round($row['balance'], 4) !== 0.0)
            ->values()
            ->all();

        return [
            'accounts' => $rows,
            'total' => round(collect($rows)->sum('balance'), 4),
        ];
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
