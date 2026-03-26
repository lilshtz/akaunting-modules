<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Payroll\Models\Payslip;

class PortalPayslips extends Controller
{
    public function index(): Response|mixed
    {
        $employee = $this->employee();

        $payslips = Payslip::where('company_id', company_id())
            ->where('employee_id', $employee->id)
            ->with(['run.calendar'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('payroll::portal.payslips.index', compact('employee', 'payslips'));
    }

    public function show(int $id): Response|mixed
    {
        $payslip = $this->query()->findOrFail($id);

        return view('payroll::portal.payslips.show', compact('payslip'));
    }

    public function download(int $id): Response|mixed
    {
        $controller = app(Payslips::class);

        return $controller->download($this->query()->findOrFail($id)->id);
    }

    protected function query()
    {
        return Payslip::where('company_id', company_id())
            ->where('employee_id', $this->employee()->id)
            ->with(['run.calendar', 'employee.contact', 'employee.department', 'items']);
    }

    protected function employee()
    {
        $user = auth()->user();

        abort_unless($user && $user->isEmployee(), 403);

        $employee = \Modules\Employees\Models\Employee::where('company_id', company_id())
            ->where('user_id', $user->id)
            ->first();

        abort_unless($employee, 403);

        return $employee;
    }
}
