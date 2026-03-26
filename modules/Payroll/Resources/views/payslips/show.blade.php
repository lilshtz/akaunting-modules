<x-layouts.admin>
    <x-slot name="title">#{{ $payslip->id }} {{ trans('payroll::general.payslip') }}</x-slot>

    <x-slot name="buttons">
        <a href="{{ route('payroll.payslips.download', $payslip->id) }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            {{ trans('payroll::general.download_pdf') }}
        </a>
        <form method="POST" action="{{ route('payroll.payslips.email', $payslip->id) }}" class="inline">
            @csrf
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('payroll::general.email_payslip') }}</button>
        </form>
    </x-slot>

    <x-slot name="content">
        @if (! $payslip->hasStoredPdf())
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ trans('payroll::general.pdf_unavailable') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.gross') }}</div><div class="text-xl font-semibold">{{ money($payslip->gross, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.benefits') }}</div><div class="text-xl font-semibold">{{ money($payslip->total_benefits, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.deductions') }}</div><div class="text-xl font-semibold">{{ money($payslip->total_deductions, setting('default.currency', 'USD')) }}</div></div>
            <div class="bg-white rounded-xl shadow-sm p-4"><div class="text-sm text-gray-500">{{ trans('payroll::general.net') }}</div><div class="text-xl font-semibold">{{ money($payslip->net, setting('default.currency', 'USD')) }}</div></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.employee_details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('employees::general.employee') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->name ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.department') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->department?->name ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.tax_number') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->contact?->tax_number ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.pay_period') }}</dt><dd class="font-medium text-right">{{ $payslip->run?->period_start?->format('M d, Y') }} - {{ $payslip->run?->period_end?->format('M d, Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.emailed_at') }}</dt><dd class="font-medium text-right">{{ $payslip->emailed_at?->format('M d, Y H:i') ?? '-' }}</dd></div>
                </dl>

                <h3 class="text-lg font-semibold mt-8 mb-4">{{ trans('payroll::general.bank_details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.bank_name_label') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->bank_name ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.bank_account') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->bank_account ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ trans('payroll::general.bank_routing') }}</dt><dd class="font-medium text-right">{{ $payslip->employee?->bank_routing ?? '-' }}</dd></div>
                </dl>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.gross_breakdown') }}</h3>
                        <div class="rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between px-4 py-3 border-b text-sm">
                                <span>{{ trans('payroll::general.base_salary') }}</span>
                                <span class="font-medium">{{ money($payslip->gross, setting('default.currency', 'USD')) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.benefits') }}</h3>
                        <div class="rounded-lg border border-gray-200">
                            @forelse ($payslip->items->where('type', 'benefit') as $item)
                                <div class="flex items-center justify-between px-4 py-3 border-b last:border-b-0 text-sm">
                                    <span>{{ $item->name }}</span>
                                    <span class="font-medium">{{ money($item->amount, setting('default.currency', 'USD')) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-500">-</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.deductions') }}</h3>
                    <div class="rounded-lg border border-gray-200">
                        @forelse ($payslip->items->where('type', 'deduction') as $item)
                            <div class="flex items-center justify-between px-4 py-3 border-b last:border-b-0 text-sm">
                                <span>{{ $item->name }}</span>
                                <span class="font-medium">{{ money($item->amount, setting('default.currency', 'USD')) }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-500">-</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
