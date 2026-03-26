<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Services\PayslipService;

class Payslips extends Controller
{
    public function __construct(protected PayslipService $service)
    {
    }

    public function show(int $id): Response|mixed
    {
        $payslip = $this->payslipQuery()->findOrFail($id);

        return view('payroll::payslips.show', compact('payslip'));
    }

    public function download(int $id): Response|mixed
    {
        $payslip = $this->payslipQuery()->findOrFail($id);

        if ($payslip->hasStoredPdf()) {
            return Storage::disk('public')->download($payslip->pdf_path, $payslip->file_name);
        }

        $pdf = $this->service->buildPdf($payslip);

        if ($pdf !== null) {
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $payslip->file_name . '"',
            ]);
        }

        return response($this->service->renderHtml($payslip), 200, ['Content-Type' => 'text/html']);
    }

    public function email(int $id): Response|mixed
    {
        $payslip = $this->payslipQuery()->findOrFail($id);

        if (! $this->sendToEmployee($payslip)) {
            flash(trans('payroll::general.email_missing'))->warning();

            return redirect()->route('payroll.payslips.show', $payslip->id);
        }

        $payslip->update(['emailed_at' => now()]);

        flash(trans('payroll::general.payslip_emailed'))->success();

        return redirect()->route('payroll.payslips.show', $payslip->id);
    }

    public function bulkEmail(int $runId): Response|mixed
    {
        $run = PayrollRun::where('company_id', company_id())
            ->with(['payslips.employee.contact', 'payslips.employee.user'])
            ->findOrFail($runId);

        $sent = 0;

        DB::transaction(function () use ($run, &$sent) {
            foreach ($run->payslips as $payslip) {
                if (! $this->sendToEmployee($payslip)) {
                    continue;
                }

                $payslip->update(['emailed_at' => now()]);
                $sent++;
            }
        });

        flash(trans('payroll::general.bulk_payslips_emailed', ['count' => $sent]))->success();

        return redirect()->route('payroll.payroll-runs.show', $run->id);
    }

    public function history(int $employeeId): Response|mixed
    {
        $employee = Employee::where('company_id', company_id())
            ->with(['contact', 'department'])
            ->findOrFail($employeeId);

        $payslips = Payslip::where('company_id', company_id())
            ->where('employee_id', $employee->id)
            ->with(['run.calendar'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('payroll::payslips.history', compact('employee', 'payslips'));
    }

    protected function sendToEmployee(Payslip $payslip): bool
    {
        $payslip->loadMissing(['employee.contact', 'employee.user', 'run.calendar']);

        $notifiable = $payslip->employee?->user ?: $payslip->employee?->contact;

        if (! $notifiable || empty($notifiable->email)) {
            return false;
        }

        $notifiable->notify(new PayslipReady($payslip));

        return true;
    }

    protected function payslipQuery()
    {
        return Payslip::where('company_id', company_id())
            ->with(['run.calendar', 'employee.contact', 'employee.department', 'items']);
    }
}
