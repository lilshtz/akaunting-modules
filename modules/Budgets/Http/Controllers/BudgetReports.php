<?php

namespace Modules\Budgets\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\Budgets\Models\Budget;
use Modules\Budgets\Services\BudgetReportService;

class BudgetReports extends Controller
{
    public function __construct(protected BudgetReportService $reports)
    {
    }

    public function show(int $budgetId): mixed
    {
        $budget = $this->findBudget($budgetId);
        $report = $this->reports->build($budget);

        return view('budgets::reports.show', compact('budget', 'report'));
    }

    public function export(Request $request, int $budgetId): mixed
    {
        $budget = $this->findBudget($budgetId);
        $report = $this->reports->build($budget);
        $format = $request->get('format', 'csv');

        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('budgets::reports.pdf', compact('budget', 'report'));

            return $pdf->download('budget-report-' . $budget->id . '.pdf');
        }

        $filename = 'budget-report-' . $budget->id . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Account Code', 'Account Name', 'Budget', 'Actual', 'Variance', 'Variance %', 'Over Budget']);

            foreach ($report['lines'] as $row) {
                fputcsv($handle, [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['budget_amount'], 2, '.', ''),
                    number_format($row['actual_amount'], 2, '.', ''),
                    number_format($row['variance'], 2, '.', ''),
                    $row['variance_percentage'] !== null ? number_format($row['variance_percentage'], 2, '.', '') . '%' : '',
                    $row['is_over_budget'] ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function findBudget(int $id): Budget
    {
        return Budget::where('company_id', company_id())
            ->with(['lines.account'])
            ->findOrFail($id);
    }
}
