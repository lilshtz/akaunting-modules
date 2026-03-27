<?php

namespace Modules\Appointments\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\Appointments\Models\Appointment;
use Modules\Appointments\Models\LeaveRequest;

class AppointmentCalendarService
{
    public function build(int $companyId, ?string $date = null, string $view = 'month'): array
    {
        $selected = $date ? Carbon::parse($date) : now();
        $view = in_array($view, ['month', 'week', 'day'], true) ? $view : 'month';

        [$start, $end] = match ($view) {
            'day' => [$selected->copy()->startOfDay(), $selected->copy()->endOfDay()],
            'week' => [$selected->copy()->startOfWeek(), $selected->copy()->endOfWeek()],
            default => [$selected->copy()->startOfMonth()->startOfWeek(), $selected->copy()->endOfMonth()->endOfWeek()],
        };

        $appointments = Appointment::where('company_id', $companyId)
            ->with(['contact', 'user'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $leaveRequests = LeaveRequest::where('company_id', $companyId)
            ->approved()
            ->with(['employee.contact', 'approver'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get();

        $days = [];

        foreach (CarbonPeriod::create($start->toDateString(), $end->toDateString()) as $day) {
            $key = $day->toDateString();
            $days[$key] = [
                'date' => $day->copy(),
                'appointments' => $appointments->where('date', $day)->values(),
                'leave' => collect(),
            ];
        }

        foreach ($leaveRequests as $request) {
            foreach (CarbonPeriod::create($request->start_date->toDateString(), $request->end_date->toDateString()) as $leaveDay) {
                $key = $leaveDay->toDateString();

                if (! isset($days[$key])) {
                    continue;
                }

                $days[$key]['leave']->push($request);
            }
        }

        return [
            'view' => $view,
            'selectedDate' => $selected,
            'periodStart' => $start,
            'periodEnd' => $end,
            'days' => collect($days),
            'appointments' => $appointments,
            'leaveRequests' => $leaveRequests,
        ];
    }
}
