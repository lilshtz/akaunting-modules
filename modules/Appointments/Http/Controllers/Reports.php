<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\Appointments\Models\Appointment;
use Modules\Appointments\Models\LeaveRequest;
use Modules\Appointments\Services\LeaveBalanceService;
use Modules\Employees\Models\Employee;

class Reports extends Controller
{
    public function __construct(protected LeaveBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $year = $request->integer('year') ?: (int) now()->year;
        $employees = Employee::where('company_id', company_id())->with('contact')->get();

        $appointmentHistory = Appointment::where('company_id', company_id())
            ->with(['contact', 'user'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->get('status')))
            ->orderByDesc('date')
            ->paginate(25, ['*'], 'appointments_page');

        $leaveSummary = $employees->map(function (Employee $employee) use ($year) {
            return [
                'employee' => $employee,
                'balances' => $this->balances->summaryForEmployee($employee, $year),
            ];
        });

        $appointmentStatuses = collect(Appointment::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => trans('appointments::general.statuses.' . $status)]);

        return view('appointments::reports.index', compact('appointmentHistory', 'leaveSummary', 'employees', 'year', 'appointmentStatuses'));
    }

    public function export(Request $request)
    {
        $year = $request->integer('year') ?: (int) now()->year;
        $employees = Employee::where('company_id', company_id())->with('contact')->get();
        $rows = [['section', 'employee', 'type', 'allowance', 'used', 'remaining']];

        foreach ($employees as $employee) {
            foreach ($this->balances->summaryForEmployee($employee, $year) as $type => $data) {
                $rows[] = ['leave', $employee->name, $type, $data['allowance'], $data['used'], $data['remaining']];
            }
        }

        $appointments = Appointment::where('company_id', company_id())
            ->with(['contact', 'user'])
            ->orderByDesc('date')
            ->get();

        $rows[] = [];
        $rows[] = ['section', 'date', 'customer', 'assigned_user', 'status', 'location'];

        foreach ($appointments as $appointment) {
            $rows[] = [
                'appointment',
                $appointment->date?->toDateString(),
                $appointment->customer_name,
                $appointment->user?->name,
                $appointment->status,
                $appointment->location,
            ];
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
            'Content-Disposition' => 'attachment; filename="appointments-leave-report.csv"',
        ]);
    }
}
