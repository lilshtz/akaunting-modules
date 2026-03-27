<?php

namespace Modules\AutoScheduleReports\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AutoScheduleReports\Models\ReportSchedule;
use Modules\Budgets\Models\Budget;
use Modules\Budgets\Services\BudgetReportService;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ReportBuilder
{
    public function __construct(
        protected AccountBalanceService $balanceService,
        protected BudgetReportService $budgetReports,
        protected ReportDateRangeResolver $dateRangeResolver
    ) {
    }

    public function generate(ReportSchedule $schedule): array
    {
        $report = $this->build($schedule);
        $timestamp = now()->format('Ymd_His');
        $extension = $schedule->format === 'excel' ? 'xls' : $schedule->format;
        $path = 'auto-schedule-reports/company-' . $schedule->company_id . '/' . $schedule->report_type . '-' . $schedule->id . '-' . $timestamp . '.' . $extension;
        $contents = $this->renderContents($schedule, $report);

        Storage::disk('local')->put($path, $contents);

        return [
            'report' => $report,
            'path' => $path,
            'name' => basename($path),
            'mime_type' => $this->mimeType($schedule->format),
        ];
    }

    public function build(ReportSchedule $schedule): array
    {
        [$dateFrom, $dateTo, $label] = $this->dateRangeResolver->resolve($schedule);

        $report = match ($schedule->report_type) {
            'pnl' => $this->profitLoss($schedule->company_id, $dateFrom, $dateTo),
            'balance_sheet' => $this->balanceSheet($schedule->company_id, $dateTo),
            'trial_balance' => $this->trialBalance($schedule->company_id, $dateFrom, $dateTo),
            'cash_flow' => $this->cashFlow($schedule->company_id, $dateFrom, $dateTo),
            'ar_aging' => $this->aging($schedule->company_id, $dateTo, 'invoice'),
            'ap_aging' => $this->aging($schedule->company_id, $dateTo, 'bill'),
            'budget_variance' => $this->budgetVariance($schedule->company_id, $dateFrom, $dateTo),
            default => $this->custom($schedule, $dateFrom, $dateTo),
        };

        return array_merge($report, [
            'report_type' => $schedule->report_type,
            'report_type_label' => $schedule->report_type_label,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_label' => $label,
            'generated_at' => now(),
        ]);
    }

    protected function renderContents(ReportSchedule $schedule, array $report): string
    {
        return match ($schedule->format) {
            'csv' => $this->toCsv($report),
            'excel' => $this->toExcel($report),
            default => app('dompdf.wrapper')
                ->loadView('auto-schedule-reports::reports.pdf', compact('report', 'schedule'))
                ->output(),
        };
    }

    protected function profitLoss(int $companyId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = [];
        $totalIncome = 0.0;
        $totalExpenses = 0.0;

        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $amount = $this->periodBalance($account->id, $dateFrom, $dateTo);

            if (abs($amount) < 0.005) {
                continue;
            }

            $rows[] = [
                $account->code,
                $account->name,
                ucfirst($account->type),
                round($amount, 2),
            ];

            if ($account->type === 'income') {
                $totalIncome += $amount;
            } else {
                $totalExpenses += $amount;
            }
        }

        return [
            'headings' => ['Code', 'Account', 'Type', 'Amount'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Income', 'value' => round($totalIncome, 2)],
                ['label' => 'Total Expenses', 'value' => round($totalExpenses, 2)],
                ['label' => 'Net Income', 'value' => round($totalIncome - $totalExpenses, 2)],
            ],
        ];
    }

    protected function balanceSheet(int $companyId, Carbon $asOfDate): array
    {
        $rows = [];
        $totals = [
            'asset' => 0.0,
            'liability' => 0.0,
            'equity' => 0.0,
        ];

        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getBalance($account->id, $asOfDate->toDateString());

            if (abs($balance) < 0.005) {
                continue;
            }

            $rows[] = [
                $account->code,
                $account->name,
                ucfirst($account->type),
                round($balance, 2),
            ];

            $totals[$account->type] += $balance;
        }

        return [
            'headings' => ['Code', 'Account', 'Type', 'Balance'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Assets', 'value' => round($totals['asset'], 2)],
                ['label' => 'Total Liabilities', 'value' => round($totals['liability'], 2)],
                ['label' => 'Total Equity', 'value' => round($totals['equity'], 2)],
            ],
        ];
    }

    protected function trialBalance(int $companyId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $trialBalance = $this->balanceService->getTrialBalance($companyId, $dateFrom->toDateString(), $dateTo->toDateString());
        $rows = [];

        foreach ($trialBalance['accounts'] ?? [] as $type => $accounts) {
            foreach ($accounts as $row) {
                $rows[] = [
                    $row['account']->code,
                    $row['account']->name,
                    ucfirst($type),
                    round((float) $row['debit'], 2),
                    round((float) $row['credit'], 2),
                ];
            }
        }

        return [
            'headings' => ['Code', 'Account', 'Type', 'Debit', 'Credit'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Grand Debit', 'value' => round((float) ($trialBalance['grand_debit'] ?? 0), 2)],
                ['label' => 'Grand Credit', 'value' => round((float) ($trialBalance['grand_credit'] ?? 0), 2)],
            ],
        ];
    }

    protected function cashFlow(int $companyId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $transactions = DB::table('transactions')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.company_id', $companyId)
            ->whereBetween('transactions.paid_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('transactions.type', 'not like', '%transfer%')
            ->orderBy('transactions.paid_at')
            ->select([
                'transactions.paid_at',
                'transactions.type',
                'transactions.amount',
                'transactions.reference',
                'transactions.description',
                DB::raw('COALESCE(accounts.name, "Unassigned") as account_name'),
            ])
            ->get();

        $rows = [];
        $cashIn = 0.0;
        $cashOut = 0.0;

        foreach ($transactions as $transaction) {
            $isIncome = str_starts_with((string) $transaction->type, 'income');
            $amount = round((float) $transaction->amount, 2);

            $rows[] = [
                Carbon::parse($transaction->paid_at)->toDateString(),
                $transaction->account_name,
                $transaction->reference ?: '',
                $transaction->description ?: '',
                $isIncome ? $amount : 0,
                $isIncome ? 0 : $amount,
            ];

            if ($isIncome) {
                $cashIn += $amount;
            } else {
                $cashOut += $amount;
            }
        }

        return [
            'headings' => ['Date', 'Account', 'Reference', 'Description', 'Cash In', 'Cash Out'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Cash In', 'value' => round($cashIn, 2)],
                ['label' => 'Cash Out', 'value' => round($cashOut, 2)],
                ['label' => 'Net Cash Flow', 'value' => round($cashIn - $cashOut, 2)],
            ],
        ];
    }

    protected function aging(int $companyId, Carbon $asOfDate, string $type): array
    {
        $rows = [];
        $totals = [
            'current' => 0.0,
            '1-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        $documents = DB::table('documents')
            ->leftJoin('transactions', function ($join) use ($asOfDate) {
                $join->on('transactions.document_id', '=', 'documents.id')
                    ->whereDate('transactions.paid_at', '<=', $asOfDate->toDateString());
            })
            ->where('documents.company_id', $companyId)
            ->where('documents.type', $type)
            ->whereDate('documents.issued_at', '<=', $asOfDate->toDateString())
            ->whereNotIn('documents.status', ['draft', 'cancelled', 'paid'])
            ->groupBy('documents.id', 'documents.document_number', 'documents.contact_name', 'documents.due_at', 'documents.amount')
            ->orderBy('documents.due_at')
            ->select([
                'documents.id',
                'documents.document_number',
                'documents.contact_name',
                'documents.due_at',
                'documents.amount',
                DB::raw('COALESCE(SUM(transactions.amount), 0) as paid_amount'),
            ])
            ->get();

        foreach ($documents as $document) {
            $outstanding = round((float) $document->amount - (float) $document->paid_amount, 2);

            if ($outstanding <= 0) {
                continue;
            }

            $dueAt = Carbon::parse($document->due_at);
            $daysPastDue = $dueAt->gt($asOfDate) ? 0 : $dueAt->diffInDays($asOfDate);
            $bucket = match (true) {
                $daysPastDue === 0 => 'current',
                $daysPastDue <= 30 => '1-30',
                $daysPastDue <= 60 => '31-60',
                $daysPastDue <= 90 => '61-90',
                default => '90+',
            };

            $totals[$bucket] += $outstanding;

            $rows[] = [
                $document->document_number,
                $document->contact_name,
                $dueAt->toDateString(),
                $bucket,
                $outstanding,
            ];
        }

        return [
            'headings' => ['Document', 'Contact', 'Due Date', 'Bucket', 'Outstanding'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Current', 'value' => round($totals['current'], 2)],
                ['label' => '1-30 Days', 'value' => round($totals['1-30'], 2)],
                ['label' => '31-60 Days', 'value' => round($totals['31-60'], 2)],
                ['label' => '61-90 Days', 'value' => round($totals['61-90'], 2)],
                ['label' => '90+ Days', 'value' => round($totals['90+'], 2)],
            ],
        ];
    }

    protected function budgetVariance(int $companyId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $budget = Budget::where('company_id', $companyId)
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('period_start', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->orWhereBetween('period_end', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->orWhere(function ($subQuery) use ($dateFrom, $dateTo) {
                        $subQuery->where('period_start', '<=', $dateFrom->toDateString())
                            ->where('period_end', '>=', $dateTo->toDateString());
                    });
            })
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('period_end')
            ->with(['lines.account'])
            ->first();

        if (! $budget) {
            return [
                'headings' => ['Message'],
                'rows' => [['No budget found for the requested period.']],
                'summary' => [],
            ];
        }

        $report = $this->budgetReports->build($budget);
        $rows = [];

        foreach ($report['lines'] as $line) {
            $rows[] = [
                $line['account']->code,
                $line['account']->name,
                round((float) $line['budget_amount'], 2),
                round((float) $line['actual_amount'], 2),
                round((float) $line['variance'], 2),
                $line['variance_percentage'] !== null ? round((float) $line['variance_percentage'], 2) . '%' : '',
            ];
        }

        return [
            'headings' => ['Code', 'Account', 'Budget', 'Actual', 'Variance', 'Variance %'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Budget', 'value' => round((float) $report['summary']['budgeted'], 2)],
                ['label' => 'Actual', 'value' => round((float) $report['summary']['actual'], 2)],
                ['label' => 'Variance', 'value' => round((float) $report['summary']['variance'], 2)],
            ],
        ];
    }

    protected function custom(ReportSchedule $schedule, Carbon $dateFrom, Carbon $dateTo): array
    {
        return [
            'headings' => ['Field', 'Value'],
            'rows' => [
                ['Schedule', '#' . $schedule->id],
                ['Period Start', $dateFrom->toDateString()],
                ['Period End', $dateTo->toDateString()],
                ['Note', 'Custom report schedules need a module-specific generator.'],
            ],
            'summary' => [],
        ];
    }

    protected function periodBalance(int $accountId, Carbon $dateFrom, Carbon $dateTo): float
    {
        $closing = $this->balanceService->getBalance($accountId, $dateTo->toDateString());
        $opening = $this->balanceService->getBalance($accountId, $dateFrom->copy()->subDay()->toDateString());

        return round($closing - $opening, 2);
    }

    protected function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [$report['report_type_label']]);
        fputcsv($handle, ['Period', $report['date_label']]);
        fputcsv($handle, []);
        fputcsv($handle, $report['headings']);

        foreach ($report['rows'] as $row) {
            fputcsv($handle, $row);
        }

        if (! empty($report['summary'])) {
            fputcsv($handle, []);

            foreach ($report['summary'] as $item) {
                fputcsv($handle, [$item['label'], $item['value']]);
            }
        }

        rewind($handle);

        return (string) stream_get_contents($handle);
    }

    protected function toExcel(array $report): string
    {
        return view('auto-schedule-reports::reports.excel', compact('report'))->render();
    }

    protected function mimeType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'excel' => 'application/vnd.ms-excel',
            default => 'application/pdf',
        };
    }
}
