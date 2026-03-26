<?php

namespace Modules\ExpenseClaims\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Employees\Models\Employee;
use Modules\ExpenseClaims\Models\ExpenseClaim;
use Modules\ExpenseClaims\Services\ClaimReportService;

class ClaimReports extends Controller
{
    public function __construct(protected ClaimReportService $reports)
    {
    }

    public function index(Request $request): Response|mixed
    {
        $employees = Employee::where('company_id', company_id())
            ->with('contact')
            ->get()
            ->pluck('name', 'id');

        $statuses = collect(ExpenseClaim::STATUSES)
            ->mapWithKeys(fn ($status) => [$status => trans('expense-claims::general.statuses.' . $status)]);

        $report = $this->reports->build($request);

        return view('expense-claims::reports.index', array_merge($report, compact('employees', 'statuses')));
    }

    public function export(Request $request)
    {
        $report = $this->reports->build($request);
        $rows = [
            ['section', 'label', 'count', 'amount'],
            ['status', 'pending', '', $report['pendingTotal']],
            ['status', 'approved', '', $report['approvedTotal']],
        ];

        foreach ($report['byEmployee'] as $employee) {
            $rows[] = ['employee', $employee->employee?->name ?? trans('general.na'), $employee->claims_count, $employee->total_amount];
        }

        foreach ($report['byCategory'] as $category) {
            $rows[] = ['category', $category->name ?: trans('general.na'), $category->item_count, $category->total_amount];
        }

        $stream = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expense-claim-report.csv"',
        ]);
    }
}
