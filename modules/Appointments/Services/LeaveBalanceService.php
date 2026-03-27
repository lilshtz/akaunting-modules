<?php

namespace Modules\Appointments\Services;

use Carbon\Carbon;
use Modules\Appointments\Models\LeaveRequest;
use Modules\Employees\Models\Employee;

class LeaveBalanceService
{
    public function labels(): array
    {
        $defaults = collect(LeaveRequest::TYPES)->mapWithKeys(
            fn (string $type) => [$type => trans('appointments::general.leave_types.' . $type)]
        )->all();

        $labels = json_decode((string) setting('appointments.leave_types', json_encode($defaults)), true);

        return is_array($labels) ? array_replace($defaults, array_filter($labels)) : $defaults;
    }

    public function allowances(): array
    {
        $defaults = [
            LeaveRequest::TYPE_VACATION => 15,
            LeaveRequest::TYPE_SICK => 10,
            LeaveRequest::TYPE_PERSONAL => 3,
            LeaveRequest::TYPE_OTHER => 0,
        ];

        $allowances = json_decode((string) setting('appointments.leave_allowances', json_encode($defaults)), true);

        if (! is_array($allowances)) {
            return $defaults;
        }

        return array_map(
            static fn ($value) => round((float) $value, 2),
            array_replace($defaults, $allowances)
        );
    }

    public function summaryForEmployee(Employee $employee, ?int $year = null): array
    {
        $year ??= (int) now()->year;
        $allowances = $this->allowances();
        $used = $this->usedByType($employee->id, $year);
        $labels = $this->labels();
        $summary = [];

        foreach (LeaveRequest::TYPES as $type) {
            $summary[$type] = [
                'label' => $labels[$type] ?? ucfirst($type),
                'allowance' => round((float) ($allowances[$type] ?? 0), 2),
                'used' => round((float) ($used[$type] ?? 0), 2),
                'remaining' => round((float) ($allowances[$type] ?? 0) - (float) ($used[$type] ?? 0), 2),
            ];
        }

        return $summary;
    }

    public function usedByType(int $employeeId, ?int $year = null): array
    {
        $year ??= (int) now()->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        return LeaveRequest::query()
            ->where('company_id', company_id())
            ->where('employee_id', $employeeId)
            ->approved()
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('type')
            ->map(fn ($requests) => round((float) $requests->sum('days'), 2))
            ->all();
    }
}
