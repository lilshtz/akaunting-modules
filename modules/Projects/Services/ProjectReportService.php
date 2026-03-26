<?php

namespace Modules\Projects\Services;

use App\Models\Banking\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;

class ProjectReportService
{
    public function build(Project $project): array
    {
        $project->loadMissing([
            'transactions.document.transactions',
            'timesheets.task',
            'timesheets.user',
        ]);

        $invoiceDocuments = $project->transactions
            ->where('document_type', 'invoice')
            ->pluck('document')
            ->filter();

        $billDocuments = $project->transactions
            ->where('document_type', 'bill')
            ->pluck('document')
            ->filter();

        $revenue = (float) $invoiceDocuments->sum(fn ($document) => (float) ($document->amount ?? 0));
        $documentCosts = (float) $billDocuments->sum(fn ($document) => (float) ($document->amount ?? 0));
        $trackedHours = (float) $project->timesheets->sum(fn ($timesheet) => (float) $timesheet->tracked_hours);
        $billableHours = (float) $project->timesheets->where('billable', true)->sum(fn ($timesheet) => (float) $timesheet->tracked_hours);
        $laborRate = (float) ($project->billing_rate ?? 0);
        $laborCost = round($trackedHours * $laborRate, 2);
        $actualCosts = round($documentCosts + $laborCost, 2);
        $profit = round($revenue - $actualCosts, 2);
        $budget = (float) ($project->budget ?? 0);
        $variance = round($budget - $actualCosts, 2);
        $variancePercentage = $budget > 0 ? round(($variance / $budget) * 100, 2) : null;
        $remainingBudget = round($budget - $actualCosts, 2);
        $overBudget = $budget > 0 && $actualCosts > $budget;

        $costEntries = $this->buildCostEntries($project, $billDocuments, $laborRate);
        $burnPoints = $this->buildBurnPoints($project, $costEntries, $budget);
        $burnRate = $this->calculateBurnRate($project, $costEntries, $actualCosts);
        $projectedCompletionCost = $this->calculateProjectedCompletionCost($project, $actualCosts, $burnRate);
        $cashFlow = $this->buildCashFlow($invoiceDocuments, $billDocuments);

        return [
            'summary' => [
                'revenue' => $revenue,
                'document_costs' => $documentCosts,
                'labor_cost' => $laborCost,
                'costs' => $actualCosts,
                'profit' => $profit,
                'tracked_hours' => $trackedHours,
                'billable_hours' => $billableHours,
                'labor_rate' => $laborRate,
            ],
            'budget' => [
                'planned' => $budget,
                'actual' => $actualCosts,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'remaining' => $remainingBudget,
                'burn_rate' => $burnRate,
                'projected_completion_cost' => $projectedCompletionCost,
                'over_budget' => $overBudget,
            ],
            'burn' => [
                'points' => $burnPoints,
                'max' => max(array_merge([$budget, $actualCosts, 1], collect($burnPoints)->pluck('actual')->all())),
            ],
            'cash_flow' => [
                'months' => $cashFlow,
                'totals' => [
                    'inflows' => round($cashFlow->sum('inflows'), 2),
                    'outflows' => round($cashFlow->sum('outflows'), 2),
                ],
            ],
        ];
    }

    protected function buildCostEntries(Project $project, Collection $billDocuments, float $laborRate): Collection
    {
        $billEntries = $billDocuments->map(function ($document) {
            $date = $document->issued_at ?: $document->created_at;

            return [
                'date' => Carbon::parse($date)->toDateString(),
                'amount' => (float) ($document->amount ?? 0),
            ];
        });

        $laborEntries = $project->timesheets->map(function ($timesheet) use ($laborRate) {
            return [
                'date' => $timesheet->started_at?->toDateString() ?: now()->toDateString(),
                'amount' => round(((float) $timesheet->tracked_hours) * $laborRate, 2),
            ];
        });

        return $billEntries
            ->concat($laborEntries)
            ->filter(fn (array $entry) => $entry['amount'] > 0)
            ->sortBy('date')
            ->values();
    }

    protected function buildBurnPoints(Project $project, Collection $costEntries, float $budget): array
    {
        if ($costEntries->isEmpty()) {
            $date = $project->start_date?->toDateString() ?: now()->toDateString();

            return [[
                'date' => $date,
                'label' => Carbon::parse($date)->format('M d'),
                'actual' => 0.0,
                'budget' => round($budget, 2),
            ]];
        }

        $grouped = $costEntries->groupBy('date');
        $cumulative = 0;

        return $grouped->map(function (Collection $entries, string $date) use (&$cumulative, $budget) {
            $cumulative += (float) $entries->sum('amount');

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('M d'),
                'actual' => round($cumulative, 2),
                'budget' => round($budget, 2),
            ];
        })->values()->all();
    }

    protected function calculateBurnRate(Project $project, Collection $costEntries, float $actualCosts): float
    {
        $firstCostDate = $costEntries->first()['date'] ?? null;
        $start = $project->start_date ?: $firstCostDate ?: $project->created_at;

        if (empty($start)) {
            return 0.0;
        }

        $days = max(1, Carbon::parse($start)->startOfDay()->diffInDays(now()->startOfDay()) + 1);

        return round($actualCosts / $days, 2);
    }

    protected function calculateProjectedCompletionCost(Project $project, float $actualCosts, float $burnRate): float
    {
        if (empty($project->end_date) || $burnRate <= 0) {
            return round($actualCosts, 2);
        }

        $remainingDays = max(0, now()->startOfDay()->diffInDays($project->end_date->copy()->startOfDay(), false));

        return round($actualCosts + ($remainingDays * $burnRate), 2);
    }

    protected function buildCashFlow(Collection $invoiceDocuments, Collection $billDocuments): Collection
    {
        $months = collect();

        $invoiceDocuments->each(function ($document) use (&$months) {
            $document->transactions
                ->filter(fn ($transaction) => str_starts_with((string) $transaction->type, Transaction::INCOME_TYPE))
                ->each(function ($transaction) use (&$months) {
                    $month = $transaction->paid_at?->format('Y-m');

                    if (empty($month)) {
                        return;
                    }

                    $months[$month]['month'] = $month;
                    $months[$month]['label'] = $transaction->paid_at->format('M Y');
                    $months[$month]['inflows'] = round(($months[$month]['inflows'] ?? 0) + (float) $transaction->amount, 2);
                    $months[$month]['outflows'] = $months[$month]['outflows'] ?? 0.0;
                });
        });

        $billDocuments->each(function ($document) use (&$months) {
            $document->transactions
                ->filter(fn ($transaction) => str_starts_with((string) $transaction->type, Transaction::EXPENSE_TYPE))
                ->each(function ($transaction) use (&$months) {
                    $month = $transaction->paid_at?->format('Y-m');

                    if (empty($month)) {
                        return;
                    }

                    $months[$month]['month'] = $month;
                    $months[$month]['label'] = $transaction->paid_at->format('M Y');
                    $months[$month]['inflows'] = $months[$month]['inflows'] ?? 0.0;
                    $months[$month]['outflows'] = round(($months[$month]['outflows'] ?? 0) + (float) $transaction->amount, 2);
                });
        });

        return $months
            ->sortKeys()
            ->values()
            ->map(function (array $month) {
                $month['net'] = round(($month['inflows'] ?? 0) - ($month['outflows'] ?? 0), 2);

                return $month;
            });
    }
}
