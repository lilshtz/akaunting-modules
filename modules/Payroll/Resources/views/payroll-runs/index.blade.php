<x-layouts.admin>
    <x-slot name="title">{{ trans('payroll::general.payroll_runs') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('payroll.payroll-runs.create') }}" kind="primary">{{ trans('payroll::general.run_payroll') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">#</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.pay_calendar') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.period_start') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.period_end') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('payroll::general.net') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr class="border-b">
                            <td class="px-4 py-3 text-sm">#{{ $run->id }}</td>
                            <td class="px-4 py-3 text-sm">{{ $run->calendar?->name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $run->period_start?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $run->period_end?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm">{{ trans('payroll::general.statuses.' . $run->status) }}</td>
                            <td class="px-4 py-3 text-sm">{{ money($run->total_net, setting('default.currency', 'USD')) }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('payroll.payroll-runs.show', $run->id) }}" class="text-purple-700 hover:underline">{{ trans('general.show') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $runs->links() }}</div>
    </x-slot>
</x-layouts.admin>
