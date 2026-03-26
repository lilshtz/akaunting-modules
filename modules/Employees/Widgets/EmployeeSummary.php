<?php

namespace Modules\Employees\Widgets;

use App\Abstracts\Widget;
use Modules\Employees\Models\Department;
use Modules\Employees\Models\Employee;

class EmployeeSummary extends Widget
{
    public $default_name = 'employees::general.employee_summary';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        $companyId = company_id();

        $totalHeadcount = Employee::where('company_id', $companyId)->active()->count();

        $byDepartment = Department::where('company_id', $companyId)
            ->withCount(['employees' => function ($q) {
                $q->active();
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'count' => $dept->employees_count,
                ];
            });

        $byType = Employee::where('company_id', $companyId)
            ->active()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $recentHires = Employee::where('company_id', $companyId)
            ->active()
            ->with('contact')
            ->orderBy('hire_date', 'desc')
            ->limit(5)
            ->get();

        return $this->view('employees::widgets.summary', compact(
            'totalHeadcount', 'byDepartment', 'byType', 'recentHires'
        ));
    }
}
