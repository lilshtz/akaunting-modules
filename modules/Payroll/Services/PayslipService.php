<?php

namespace Modules\Payroll\Services;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Payroll\Models\PayItem;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\PayrollRunEmployee;

class PayslipService
{
    public function __construct(protected ViewFactory $view)
    {
    }

    public function generateForRun(PayrollRun $run): Collection
    {
        $run->loadMissing(['calendar', 'employees.employee.contact', 'employees.employee.department']);

        return $run->employees->map(function (PayrollRunEmployee $line) use ($run) {
            $payslip = Payslip::updateOrCreate(
                [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $line->employee_id,
                ],
                [
                    'company_id' => $run->company_id,
                    'gross' => $line->gross_amount,
                    'total_benefits' => $line->benefit_amount,
                    'total_deductions' => $line->deduction_amount,
                    'net' => $line->net_amount,
                    'pdf_path' => null,
                ]
            );

            $this->syncItems($payslip, $line);
            $this->storePdf($payslip->fresh(['run.calendar', 'employee.contact', 'employee.department', 'items']));

            return $payslip;
        });
    }

    public function syncItems(Payslip $payslip, PayrollRunEmployee $line): void
    {
        $payslip->items()->delete();

        foreach (['benefit', 'deduction'] as $type) {
            foreach ($this->buildItems($type, $line) as $item) {
                $payslip->items()->create($item);
            }
        }
    }

    public function storePdf(Payslip $payslip): ?string
    {
        $pdf = $this->buildPdf($payslip);

        if ($pdf === null) {
            return null;
        }

        $path = $this->pdfPath($payslip);

        Storage::disk('public')->put($path, $pdf);
        $payslip->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    public function buildPdf(Payslip $payslip): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll::payslips.pdf', [
            'payslip' => $payslip->loadMissing(['run.calendar', 'employee.contact', 'employee.department', 'items']),
            'company' => company(),
            'currency' => setting('default.currency', 'USD'),
        ])->output();
    }

    public function renderHtml(Payslip $payslip): string
    {
        return $this->view->make('payroll::payslips.pdf', [
            'payslip' => $payslip->loadMissing(['run.calendar', 'employee.contact', 'employee.department', 'items']),
            'company' => company(),
            'currency' => setting('default.currency', 'USD'),
        ])->render();
    }

    protected function buildItems(string $type, PayrollRunEmployee $line): array
    {
        $target = round((float) ($type === 'benefit' ? $line->benefit_amount : $line->deduction_amount), 4);
        $items = $this->defaultItems($type)->map(function (PayItem $item) use ($line) {
            $amount = (float) ($item->default_amount ?? 0);

            if ($item->is_percentage) {
                $amount = $line->gross_amount * ($amount / 100);
            }

            return [
                'pay_item_id' => $item->id,
                'type' => $item->type,
                'name' => $item->name,
                'amount' => round($amount, 4),
                'is_percentage' => $item->is_percentage,
                'percentage_of' => $item->is_percentage ? 'gross' : null,
            ];
        })->all();

        $current = round(collect($items)->sum('amount'), 4);
        $difference = round($target - $current, 4);

        if ($difference !== 0.0) {
            if (! empty($items)) {
                $items[array_key_last($items)]['amount'] = round($items[array_key_last($items)]['amount'] + $difference, 4);
            } elseif ($target > 0) {
                $items[] = [
                    'pay_item_id' => null,
                    'type' => $type,
                    'name' => trans('payroll::general.manual_' . $type),
                    'amount' => $target,
                    'is_percentage' => false,
                    'percentage_of' => null,
                ];
            }
        }

        return array_values(array_filter($items, fn (array $item) => round((float) $item['amount'], 4) !== 0.0));
    }

    protected function defaultItems(string $type): Collection
    {
        $ids = collect(json_decode((string) setting('payroll.default_' . $type . '_items', '[]'), true))
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PayItem::where('company_id', company_id())
            ->enabled()
            ->type($type)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    protected function pdfPath(Payslip $payslip): string
    {
        return 'payroll/payslips/company-' . $payslip->company_id
            . '/run-' . $payslip->payroll_run_id
            . '/payslip-' . $payslip->id . '.pdf';
    }
}
