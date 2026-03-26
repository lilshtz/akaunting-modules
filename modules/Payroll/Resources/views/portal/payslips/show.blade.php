<x-layouts.portal>
    <x-slot name="title">#{{ $payslip->id }} {{ trans('payroll::general.payslip') }}</x-slot>

    <div class="mb-4">
        <a href="{{ url('portal/payroll/payslips/' . $payslip->id . '/download') }}" class="inline-flex items-center rounded-lg bg-purple-600 px-4 py-2 text-white">
            {{ trans('payroll::general.download_pdf') }}
        </a>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.employee_details') }}</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.pay_period') }}</dt><dd class="font-medium text-right">{{ $payslip->run?->period_start?->format('M d, Y') }} - {{ $payslip->run?->period_end?->format('M d, Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.gross') }}</dt><dd class="font-medium text-right">{{ money($payslip->gross, setting('default.currency', 'USD')) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.benefits') }}</dt><dd class="font-medium text-right">{{ money($payslip->total_benefits, setting('default.currency', 'USD')) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.deductions') }}</dt><dd class="font-medium text-right">{{ money($payslip->total_deductions, setting('default.currency', 'USD')) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.net') }}</dt><dd class="font-medium text-right">{{ money($payslip->net, setting('default.currency', 'USD')) }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.deductions') }} / {{ trans('payroll::general.benefits') }}</h3>
            <div class="space-y-3">
                @forelse ($payslip->items as $item)
                    <div class="flex justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm">
                        <span>{{ $item->name }} <span class="text-gray-500">({{ trans('payroll::general.types.' . $item->type) }})</span></span>
                        <span class="font-medium">{{ money($item->amount, setting('default.currency', 'USD')) }}</span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">-</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.portal>
