<x-layouts.admin>
    <x-slot name="title">{{ $calendar->name }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('payroll.payroll-runs.create', ['pay_calendar_id' => $calendar->id]) }}" kind="primary">{{ trans('payroll::general.run_payroll') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.pay_calendar') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt>{{ trans('payroll::general.frequency') }}</dt><dd>{{ trans('payroll::general.frequencies.' . $calendar->frequency) }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('general.start_date') }}</dt><dd>{{ $calendar->start_date?->format('M d, Y') }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('payroll::general.next_run_date') }}</dt><dd>{{ $calendar->next_run_date?->format('M d, Y') }}</dd></div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.employees') }}</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($calendar->employees as $employee)
                        <li>{{ $employee->name }}</li>
                    @empty
                        <li class="text-gray-500">{{ trans('payroll::general.no_calendar_employees') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">{{ trans('payroll::general.history') }}</h3>
            <div class="space-y-3">
                @forelse ($calendar->runs as $run)
                    <div class="flex items-center justify-between border rounded-lg px-4 py-3">
                        <div>
                            <div class="font-medium">
                                <a href="{{ route('payroll.payroll-runs.show', $run->id) }}" class="text-purple-700 hover:underline">
                                    #{{ $run->id }} {{ $run->period_start?->format('M d, Y') }} - {{ $run->period_end?->format('M d, Y') }}
                                </a>
                            </div>
                            <div class="text-sm text-gray-500">{{ trans('payroll::general.statuses.' . $run->status) }}</div>
                        </div>
                        <div class="text-sm">{{ money($run->total_net, setting('default.currency', 'USD')) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ trans('general.no_records') }}</p>
                @endforelse
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
