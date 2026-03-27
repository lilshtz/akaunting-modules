<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.leave_request') }}</x-slot>

    <x-slot name="buttons">
        @if ($leave->status === 'pending')
            <form method="POST" action="{{ route('appointments.leave.approve', $leave->id) }}" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg">{{ trans('general.approve') }}</button>
            </form>
        @endif
    </x-slot>

    <x-slot name="content">
        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt>{{ trans('employees::general.employee') }}</dt><dd>{{ $leave->employee?->name }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('general.type') }}</dt><dd>{{ $leave->type_label }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.approver') }}</dt><dd>{{ $leave->approver?->name ?: trans('general.na') }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.date_range') }}</dt><dd>{{ $leave->start_date?->format('M d, Y') }} - {{ $leave->end_date?->format('M d, Y') }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.days') }}</dt><dd>{{ $leave->days }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('general.status') }}</dt><dd>{{ $leave->status_label }}</dd></div>
                </dl>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-2">{{ trans('appointments::general.reason') }}</h3>
                    <div class="text-sm text-gray-600 whitespace-pre-line">{{ $leave->reason ?: trans('general.na') }}</div>
                </div>

                @if ($leave->status === 'pending')
                    <form method="POST" action="{{ route('appointments.leave.refuse', $leave->id) }}" class="mt-6 space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.refusal_reason') }}</label>
                            <textarea name="reason" rows="3" class="w-full rounded-lg border-gray-300" required></textarea>
                        </div>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg">{{ trans('general.refuse') }}</button>
                    </form>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">{{ trans('appointments::general.leave_summary') }}</h3>
                <div class="space-y-3">
                    @foreach ($balances as $balance)
                        <div class="rounded-lg border p-3">
                            <div class="font-medium">{{ $balance['label'] }}</div>
                            <div class="text-sm text-gray-600">{{ trans('appointments::general.allowance') }}: {{ $balance['allowance'] }}</div>
                            <div class="text-sm text-gray-600">{{ trans('appointments::general.used') }}: {{ $balance['used'] }}</div>
                            <div class="text-sm text-gray-600">{{ trans('appointments::general.remaining') }}: {{ $balance['remaining'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
