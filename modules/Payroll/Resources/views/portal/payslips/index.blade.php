<x-layouts.portal>
    <x-slot name="title">{{ trans('payroll::general.self_service_payslips') }}</x-slot>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.pay_period') }}</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.net') }}</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.emailed_at') }}</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payslips as $payslip)
                    <tr class="border-b">
                        <td class="px-4 py-3 text-sm">{{ $payslip->run?->period_start?->format('M d, Y') }} - {{ $payslip->run?->period_end?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ money($payslip->net, setting('default.currency', 'USD')) }}</td>
                        <td class="px-4 py-3 text-sm">{{ $payslip->emailed_at?->format('M d, Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm"><a href="{{ url('portal/payroll/payslips/' . $payslip->id) }}" class="text-purple-700 hover:underline">{{ trans('general.show') }}</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">{{ trans('general.no_records') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payslips->links() }}
    </div>
</x-layouts.portal>
