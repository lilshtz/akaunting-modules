<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Http\Requests\PayCalendarStore;
use Modules\Payroll\Http\Requests\PayCalendarUpdate;
use Modules\Payroll\Models\PayCalendar;

class PayCalendars extends Controller
{
    public function index()
    {
        $calendars = PayCalendar::where('company_id', company_id())
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(25);

        return view('payroll::pay-calendars.index', compact('calendars'));
    }

    public function create()
    {
        $employees = $this->employees();

        return view('payroll::pay-calendars.create', compact('employees'));
    }

    public function store(PayCalendarStore $request)
    {
        $calendar = PayCalendar::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'frequency' => $request->get('frequency'),
            'start_date' => $request->get('start_date'),
            'next_run_date' => $request->get('next_run_date'),
            'enabled' => $request->has('enabled'),
        ]);

        $calendar->employees()->sync($this->companyEmployeeIds($request->get('employee_ids', [])));

        flash(trans('messages.success.added', ['type' => trans('payroll::general.pay_calendar')]))->success();

        return redirect()->route('payroll.pay-calendars.index');
    }

    public function show(int $id)
    {
        $calendar = PayCalendar::where('company_id', company_id())
            ->with(['employees.contact', 'runs' => fn ($query) => $query->latest('period_end')])
            ->findOrFail($id);

        return view('payroll::pay-calendars.show', compact('calendar'));
    }

    public function edit(int $id)
    {
        $calendar = PayCalendar::where('company_id', company_id())->with('employees')->findOrFail($id);
        $employees = $this->employees();
        $selectedEmployeeIds = $calendar->employees->pluck('id')->all();

        return view('payroll::pay-calendars.edit', compact('calendar', 'employees', 'selectedEmployeeIds'));
    }

    public function update(int $id, PayCalendarUpdate $request)
    {
        $calendar = PayCalendar::where('company_id', company_id())->findOrFail($id);

        $calendar->update([
            'name' => $request->get('name'),
            'frequency' => $request->get('frequency'),
            'start_date' => $request->get('start_date'),
            'next_run_date' => $request->get('next_run_date'),
            'enabled' => $request->has('enabled'),
        ]);

        $calendar->employees()->sync($this->companyEmployeeIds($request->get('employee_ids', [])));

        flash(trans('messages.success.updated', ['type' => trans('payroll::general.pay_calendar')]))->success();

        return redirect()->route('payroll.pay-calendars.show', $calendar->id);
    }

    public function destroy(int $id)
    {
        $calendar = PayCalendar::where('company_id', company_id())->findOrFail($id);
        $calendar->delete();

        flash(trans('messages.success.deleted', ['type' => trans('payroll::general.pay_calendar')]))->success();

        return redirect()->route('payroll.pay-calendars.index');
    }

    protected function employees()
    {
        return Employee::where('company_id', company_id())
            ->active()
            ->with('contact')
            ->orderBy('contact_id')
            ->get();
    }

    protected function companyEmployeeIds(array $employeeIds): array
    {
        return Employee::where('company_id', company_id())
            ->whereIn('id', $employeeIds)
            ->pluck('id')
            ->all();
    }
}
