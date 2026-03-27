<?php

namespace Modules\AutoScheduleReports\Services;

use Illuminate\Support\Carbon;
use Modules\AutoScheduleReports\Models\ReportSchedule;

class ReportDateRangeResolver
{
    public function resolve(ReportSchedule $schedule): array
    {
        $anchor = $schedule->next_run?->copy() ?? now();

        return match ($schedule->date_range_type) {
            'previous_month' => $this->previousMonth($anchor),
            'previous_quarter' => $this->previousQuarter($anchor),
            'custom' => $this->custom($schedule),
            default => $this->yearToDate($anchor),
        };
    }

    protected function previousMonth(Carbon $anchor): array
    {
        $from = $anchor->copy()->subMonthNoOverflow()->startOfMonth();
        $to = $from->copy()->endOfMonth();

        return [$from, $to, $from->format('F Y')];
    }

    protected function previousQuarter(Carbon $anchor): array
    {
        $from = $anchor->copy()->subQuarter()->firstOfQuarter();
        $to = $from->copy()->lastOfQuarter();

        return [$from, $to, 'Q' . $from->quarter . ' ' . $from->year];
    }

    protected function yearToDate(Carbon $anchor): array
    {
        $to = $anchor->copy()->subDay()->endOfDay();
        $from = $to->copy()->startOfYear();

        return [$from, $to, $from->year . ' YTD'];
    }

    protected function custom(ReportSchedule $schedule): array
    {
        $from = ($schedule->custom_date_from ?? now()->startOfMonth())->copy()->startOfDay();
        $to = ($schedule->custom_date_to ?? now())->copy()->endOfDay();

        return [$from, $to, $from->toDateString() . ' to ' . $to->toDateString()];
    }
}
