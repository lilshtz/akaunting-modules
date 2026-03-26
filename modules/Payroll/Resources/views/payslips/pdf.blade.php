<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $payslip->file_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; margin: 28px; }
        .header, .summary, .section { margin-bottom: 24px; }
        .brand { font-size: 24px; font-weight: bold; color: #111827; }
        .muted { color: #6b7280; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; width: 50%; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 14px; }
        table.table { width: 100%; border-collapse: collapse; }
        table.table th, table.table td { border-bottom: 1px solid #e5e7eb; padding: 8px 0; text-align: left; }
        table.table th:last-child, table.table td:last-child { text-align: right; }
        .totals td { padding: 6px 0; }
        .net { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $company?->name ?? config('app.name') }}</div>
        <div class="muted">{{ trans('payroll::general.payslip') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td style="padding-right: 12px;">
                <div class="card">
                    <strong>{{ trans('payroll::general.employee_details') }}</strong>
                    <div style="margin-top: 10px;">{{ trans('employees::general.employee') }}: {{ $payslip->employee?->name ?? '-' }}</div>
                    <div>{{ trans('payroll::general.department') }}: {{ $payslip->employee?->department?->name ?? '-' }}</div>
                    <div>{{ trans('payroll::general.tax_number') }}: {{ $payslip->employee?->contact?->tax_number ?? '-' }}</div>
                    <div>{{ trans('payroll::general.pay_period') }}: {{ $payslip->run?->period_start?->format('M d, Y') }} - {{ $payslip->run?->period_end?->format('M d, Y') }}</div>
                </div>
            </td>
            <td style="padding-left: 12px;">
                <div class="card">
                    <strong>{{ trans('payroll::general.bank_details') }}</strong>
                    <div style="margin-top: 10px;">{{ trans('payroll::general.bank_name_label') }}: {{ $payslip->employee?->bank_name ?? '-' }}</div>
                    <div>{{ trans('payroll::general.bank_account') }}: {{ $payslip->employee?->bank_account ?? '-' }}</div>
                    <div>{{ trans('payroll::general.bank_routing') }}: {{ $payslip->employee?->bank_routing ?? '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <strong>{{ trans('payroll::general.gross_breakdown') }}</strong>
        <table class="table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th>{{ trans('payroll::general.base_salary') }}</th>
                    <th>{{ trans('payroll::general.gross') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payslip->run?->calendar?->name ?? trans('payroll::general.payroll_run') }}</td>
                    <td>{{ money($payslip->gross, $currency) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td style="padding-right: 12px;">
                <div class="section">
                    <strong>{{ trans('payroll::general.benefits') }}</strong>
                    <table class="table" style="margin-top: 8px;">
                        <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payslip->items->where('type', 'benefit') as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ money($item->amount, $currency) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">-</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td>
            <td style="padding-left: 12px;">
                <div class="section">
                    <strong>{{ trans('payroll::general.deductions') }}</strong>
                    <table class="table" style="margin-top: 8px;">
                        <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payslip->items->where('type', 'deduction') as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ money($item->amount, $currency) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">-</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="summary card">
        <table class="totals" style="width: 100%;">
            <tr><td>{{ trans('payroll::general.gross') }}</td><td style="text-align: right;">{{ money($payslip->gross, $currency) }}</td></tr>
            <tr><td>{{ trans('payroll::general.benefits') }}</td><td style="text-align: right;">{{ money($payslip->total_benefits, $currency) }}</td></tr>
            <tr><td>{{ trans('payroll::general.deductions') }}</td><td style="text-align: right;">{{ money($payslip->total_deductions, $currency) }}</td></tr>
            <tr><td class="net">{{ trans('payroll::general.net') }}</td><td class="net" style="text-align: right;">{{ money($payslip->net, $currency) }}</td></tr>
        </table>
    </div>
</body>
</html>
