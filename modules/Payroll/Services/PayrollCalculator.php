<?php

namespace Modules\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\PayCalendar;
use Modules\Payroll\Models\PayItem;

class PayrollCalculator
{
    public function buildRunLines(PayCalendar $calendar, Carbon $periodStart, Carbon $periodEnd): array
    {
        $defaultBenefits = $this->loadDefaultItems('benefit');
        $defaultDeductions = $this->loadDefaultItems('deduction');

        $lines = [];
        $totals = [
            'gross' => 0.0,
            'deductions' => 0.0,
            'net' => 0.0,
        ];

        $calendar->loadMissing(['employees.contact']);

        foreach ($calendar->employees->where('status', 'active') as $employee) {
            $gross = $this->calculateGross($employee, $calendar, $periodStart, $periodEnd);
            $benefits = $this->calculateItemTotal($defaultBenefits, $gross);
            $deductions = $this->calculateItemTotal($defaultDeductions, $gross);
            $net = max(0, round($gross + $benefits - $deductions, 4));

            $lines[] = [
                'employee_id' => $employee->id,
                'gross_amount' => $gross,
                'benefit_amount' => $benefits,
                'deduction_amount' => $deductions,
                'net_amount' => $net,
                'notes' => null,
            ];

            $totals['gross'] += $gross + $benefits;
            $totals['deductions'] += $deductions;
            $totals['net'] += $net;
        }

        return [
            'lines' => $lines,
            'totals' => array_map(fn ($value) => round($value, 4), $totals),
        ];
    }

    public function calculateGross(Employee $employee, PayCalendar $calendar, Carbon $periodStart, Carbon $periodEnd): float
    {
        $salary = (float) ($employee->salary ?? 0);

        if ($salary <= 0) {
            return 0.0;
        }

        $annualized = match ($employee->salary_type) {
            'hourly' => $salary * ((float) setting('payroll.hours_per_week', 40)) * 52,
            'weekly' => $salary * 52,
            'biweekly' => $salary * 26,
            'monthly' => $salary * 12,
            'yearly' => $salary,
            default => $salary * $this->periodsPerYear($calendar, $periodStart, $periodEnd),
        };

        $divisor = $this->periodsPerYear($calendar, $periodStart, $periodEnd);

        if ($divisor <= 0) {
            return 0.0;
        }

        return round($annualized / $divisor, 4);
    }

    protected function loadDefaultItems(string $type): Collection
    {
        $key = 'payroll.default_' . $type . '_items';
        $ids = collect(json_decode((string) setting($key, '[]'), true))
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PayItem::where('company_id', company_id())
            ->enabled()
            ->type($type)
            ->whereIn('id', $ids)
            ->get();
    }

    protected function calculateItemTotal(Collection $items, float $gross): float
    {
        return round($items->sum(function (PayItem $item) use ($gross) {
            $amount = (float) ($item->default_amount ?? 0);

            if ($item->is_percentage) {
                return $gross * ($amount / 100);
            }

            return $amount;
        }), 4);
    }

    protected function periodsPerYear(PayCalendar $calendar, Carbon $periodStart, Carbon $periodEnd): float
    {
        return match ($calendar->frequency) {
            'weekly' => 52,
            'biweekly' => 26,
            'monthly' => 12,
            'custom' => max(1, round(365 / max(1, $periodStart->diffInDays($periodEnd) + 1), 4)),
            default => 12,
        };
    }
}
