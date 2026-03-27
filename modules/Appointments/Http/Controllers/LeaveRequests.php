<?php

namespace Modules\Appointments\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\LeaveRequestStore;
use Modules\Appointments\Http\Requests\LeaveRequestUpdate;
use Modules\Appointments\Models\LeaveRequest;
use Modules\Appointments\Notifications\LeaveRequestStatusUpdated;
use Modules\Appointments\Notifications\LeaveRequestSubmitted;
use Modules\Appointments\Services\LeaveBalanceService;
use Modules\Employees\Models\Employee;

class LeaveRequests extends Controller
{
    public function __construct(protected LeaveBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $query = LeaveRequest::where('company_id', company_id())
            ->with(['employee.contact', 'approver']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->integer('year'));
        }

        $requests = $query->orderByDesc('created_at')->paginate(25);
        $employees = $this->employees()->pluck('name', 'id');
        $approvers = $this->approvers();
        $statuses = collect(LeaveRequest::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => trans('appointments::general.leave_statuses.' . $status)]);
        $types = $this->balances->labels();

        return view('appointments::leave.index', compact('requests', 'employees', 'approvers', 'statuses', 'types'));
    }

    public function create()
    {
        return view('appointments::leave.create', $this->formData());
    }

    public function store(LeaveRequestStore $request)
    {
        $leave = LeaveRequest::create([
            'company_id' => company_id(),
            'employee_id' => $request->integer('employee_id'),
            'approver_id' => $request->integer('approver_id') ?: null,
            'type' => $request->get('type'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'days' => $this->calculateDays($request->get('start_date'), $request->get('end_date')),
            'status' => LeaveRequest::STATUS_PENDING,
            'reason' => $request->get('reason'),
        ]);

        $leave->loadMissing(['employee.user', 'approver']);

        if ($leave->approver) {
            $leave->approver->notify(new LeaveRequestSubmitted($leave));
        }

        flash(trans('messages.success.added', ['type' => trans('appointments::general.leave_request')]))->success();

        return redirect()->route('appointments.leave.show', $leave->id);
    }

    public function show(int $id)
    {
        $leave = LeaveRequest::where('company_id', company_id())
            ->with(['employee.contact', 'employee.user', 'approver'])
            ->findOrFail($id);
        $balances = $this->balances->summaryForEmployee($leave->employee, (int) $leave->start_date->format('Y'));

        return view('appointments::leave.show', compact('leave', 'balances'));
    }

    public function edit(int $id)
    {
        $leave = LeaveRequest::where('company_id', company_id())->findOrFail($id);

        if ($leave->status === LeaveRequest::STATUS_APPROVED) {
            flash(trans('appointments::general.messages.leave_locked'))->warning();

            return redirect()->route('appointments.leave.show', $leave->id);
        }

        return view('appointments::leave.edit', array_merge($this->formData(), compact('leave')));
    }

    public function update(int $id, LeaveRequestUpdate $request)
    {
        $leave = LeaveRequest::where('company_id', company_id())->findOrFail($id);

        if ($leave->status === LeaveRequest::STATUS_APPROVED) {
            flash(trans('appointments::general.messages.leave_locked'))->warning();

            return redirect()->route('appointments.leave.show', $leave->id);
        }

        $leave->update([
            'employee_id' => $request->integer('employee_id'),
            'approver_id' => $request->integer('approver_id') ?: null,
            'type' => $request->get('type'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'days' => $this->calculateDays($request->get('start_date'), $request->get('end_date')),
            'status' => LeaveRequest::STATUS_PENDING,
            'reason' => $request->get('reason'),
            'approved_at' => null,
            'refused_at' => null,
            'refusal_reason' => null,
        ]);

        flash(trans('messages.success.updated', ['type' => trans('appointments::general.leave_request')]))->success();

        return redirect()->route('appointments.leave.show', $leave->id);
    }

    public function destroy(int $id)
    {
        $leave = LeaveRequest::where('company_id', company_id())->findOrFail($id);

        if ($leave->status === LeaveRequest::STATUS_APPROVED) {
            flash(trans('appointments::general.messages.leave_locked'))->warning();

            return redirect()->route('appointments.leave.index');
        }

        $leave->delete();

        flash(trans('messages.success.deleted', ['type' => trans('appointments::general.leave_request')]))->success();

        return redirect()->route('appointments.leave.index');
    }

    public function approve(int $id)
    {
        $leave = LeaveRequest::where('company_id', company_id())
            ->with(['employee.user', 'employee.contact'])
            ->findOrFail($id);

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            flash(trans('appointments::general.messages.invalid_leave_status'))->warning();

            return redirect()->route('appointments.leave.show', $leave->id);
        }

        $leave->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
            'refused_at' => null,
            'refusal_reason' => null,
        ]);

        if ($leave->employee?->user) {
            $leave->employee->user->notify(new LeaveRequestStatusUpdated($leave));
        }

        flash(trans('appointments::general.messages.leave_approved'))->success();

        return redirect()->route('appointments.leave.show', $leave->id);
    }

    public function refuse(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $leave = LeaveRequest::where('company_id', company_id())
            ->with(['employee.user', 'employee.contact'])
            ->findOrFail($id);

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            flash(trans('appointments::general.messages.invalid_leave_status'))->warning();

            return redirect()->route('appointments.leave.show', $leave->id);
        }

        $reason = $request->get('reason');

        $leave->update([
            'status' => LeaveRequest::STATUS_REFUSED,
            'approved_at' => null,
            'refused_at' => now(),
            'refusal_reason' => $reason,
        ]);

        if ($leave->employee?->user) {
            $leave->employee->user->notify(new LeaveRequestStatusUpdated($leave, $reason));
        }

        flash(trans('appointments::general.messages.leave_refused'))->success();

        return redirect()->route('appointments.leave.show', $leave->id);
    }

    protected function formData(): array
    {
        $employees = $this->employees()->pluck('name', 'id');
        $approvers = $this->approvers();
        $types = $this->balances->labels();

        return compact('employees', 'approvers', 'types');
    }

    protected function employees()
    {
        return Employee::where('company_id', company_id())
            ->active()
            ->with('contact')
            ->orderBy('contact_id')
            ->get();
    }

    protected function approvers()
    {
        return User::query()
            ->whereHas('companies', fn ($query) => $query->where('companies.id', company_id()))
            ->enabled()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function calculateDays(string $startDate, string $endDate): float
    {
        return round((float) Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1, 2);
    }
}
