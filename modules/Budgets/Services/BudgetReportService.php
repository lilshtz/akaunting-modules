<?php

namespace Modules\Budgets\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Budgets\Models\Budget;
use Modules\DoubleEntry\Models\JournalLine;
use Modules\DoubleEntry\Services\AccountBalanceService;

class BudgetReportService
{
    public function build(Budget $budget): array
    {
        $budget->loadMissing('lines.account');

        $lineData = $this->buildLineData($budget);
        $totalBudget = round((float) $lineData->sum('budget_amount'), 2);
        $totalActual = round((float) $lineData->sum('actual_amount'), 2);
        $variance = round($totalBudget - $totalActual, 2);
        $variancePercentage = $totalBudget > 0 ? round(($variance / $totalBudget) * 100, 2) : null;
        $overBudgetCount = $lineData->where('is_over_budget', true)->count();
        $chartData = $this->buildChartData($budget, $lineData);
        $topVariances = $lineData->sortBy(fn (array $row) => abs($row['variance']))->reverse()->take(5)->values();

        return [
            'summary' => [
                'budgeted' => $totalBudget,
                'actual' => $totalActual,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'over_budget_count' => $overBudgetCount,
                'account_count' => $lineData->count(),
            ],
            'lines' => $lineData->values(),
            'top_variances' => $topVariances,
            'chart' => $chartData,
        ];
    }

    protected function buildLineData(Budget $budget): Collection
    {
        $actuals = $this->getActualsByAccount($budget);

        return $budget->lines
            ->filter(fn ($line) => $line->account !== null)
            ->sortBy(fn ($line) => $line->account->code)
            ->map(function ($line) use ($actuals) {
                $account = $line->account;
                $actual = round((float) ($actuals[$line->account_id] ?? 0), 2);
                $budgetAmount = round((float) $line->amount, 2);
                $variance = round($budgetAmount - $actual, 2);
                $variancePercentage = $budgetAmount != 0.0 ? round(($variance / $budgetAmount) * 100, 2) : null;

                return [
                    'account' => $account,
                    'budget_amount' => $budgetAmount,
                    'actual_amount' => $actual,
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'is_over_budget' => $budgetAmount > 0 && $actual > $budgetAmount,
                ];
            });
    }

    protected function getActualsByAccount(Budget $budget): array
    {
        $accountIds = $budget->lines->pluck('account_id')->filter()->unique()->values();

        if ($accountIds->isEmpty()) {
            return [];
        }

        $rows = JournalLine::query()
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
            ->where('double_entry_journals.company_id', $budget->company_id)
            ->where('double_entry_journals.status', 'posted')
            ->whereBetween('double_entry_journals.date', [
                $budget->period_start->toDateString(),
                $budget->period_end->toDateString(),
            ])
            ->whereIn('double_entry_journal_lines.account_id', $accountIds)
            ->groupBy('double_entry_journal_lines.account_id', 'double_entry_accounts.type')
            ->select([
                'double_entry_journal_lines.account_id',
                'double_entry_accounts.type',
                DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
            ])
            ->get();

        $actuals = [];

        foreach ($rows as $row) {
            $isDebitNormal = AccountBalanceService::normalBalanceSide($row->type) === 'debit';
            $actuals[$row->account_id] = $isDebitNormal
                ? (float) $row->total_debit - (float) $row->total_credit
                : (float) $row->total_credit - (float) $row->total_debit;
        }

        return $actuals;
    }

    protected function buildChartData(Budget $budget, Collection $lineData): array
    {
        $months = [];
        $budgetSeries = [];
        $actualSeries = [];
        $accountIds = $budget->lines->pluck('account_id')->filter()->unique()->values();
        $periodCount = max(1, Carbon::parse($budget->period_start)->startOfMonth()->diffInMonths(Carbon::parse($budget->period_end)->startOfMonth()) + 1);
        $monthlyBudget = $periodCount > 0 ? round((float) $lineData->sum('budget_amount') / $periodCount, 2) : 0;

        $monthlyActuals = JournalLine::query()
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
            ->where('double_entry_journals.company_id', $budget->company_id)
            ->where('double_entry_journals.status', 'posted')
            ->whereBetween('double_entry_journals.date', [
                $budget->period_start->toDateString(),
                $budget->period_end->toDateString(),
            ])
            ->when($accountIds->isNotEmpty(), fn ($query) => $query->whereIn('double_entry_journal_lines.account_id', $accountIds))
            ->groupBy(DB::raw("DATE_FORMAT(double_entry_journals.date, '%Y-%m')"), 'double_entry_accounts.type')
            ->select([
                DB::raw("DATE_FORMAT(double_entry_journals.date, '%Y-%m') as budget_month"),
                'double_entry_accounts.type',
                DB::raw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit'),
            ])
            ->get()
            ->groupBy('budget_month');

        $cursor = $budget->period_start->copy()->startOfMonth();
        $end = $budget->period_end->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $months[] = $cursor->format('M Y');
            $budgetSeries[] = $monthlyBudget;

            $actualAmount = 0.0;
            foreach ($monthlyActuals->get($key, collect()) as $row) {
                $isDebitNormal = AccountBalanceService::normalBalanceSide($row->type) === 'debit';
                $actualAmount += $isDebitNormal
                    ? (float) $row->total_debit - (float) $row->total_credit
                    : (float) $row->total_credit - (float) $row->total_debit;
            }

            $actualSeries[] = round($actualAmount, 2);
            $cursor->addMonth();
        }

        return [
            'labels' => $months,
            'budget' => $budgetSeries,
            'actual' => $actualSeries,
        ];
    }
}
