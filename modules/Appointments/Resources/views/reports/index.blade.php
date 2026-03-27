<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.reports') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.reports.export', request()->query()) }}">{{ trans('general.export') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <form method="GET" action="{{ route('appointments.reports.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('employees::general.employee') }}</label>
                        <select name="user_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.all') }}</option>
                            @foreach ($employees as $employee)
                                @if ($employee->user_id)
                                    <option value="{{ $employee->user_id }}" @selected((string) request('user_id') === (string) $employee->user_id)>{{ $employee->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                        <select name="status" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.all') }}</option>
                            @foreach ($appointmentStatuses as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.year') }}</label>
                        <input type="number" name="year" value="{{ $year }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 font-semibold border-b">{{ trans('appointments::general.appointment_history') }}</div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.date') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.customer') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.assigned_user') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointmentHistory as $appointment)
                            <tr class="border-b">
                                <td class="px-4 py-3 text-sm">{{ $appointment->date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->customer_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->user?->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->status_label }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 font-semibold border-b">{{ trans('appointments::general.leave_summary') }}</div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.employee') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.type') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.allowance') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.used') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.remaining') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveSummary as $row)
                            @foreach ($row['balances'] as $balance)
                                <tr class="border-b">
                                    <td class="px-4 py-3 text-sm">{{ $row['employee']->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $balance['label'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $balance['allowance'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $balance['used'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $balance['remaining'] }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $appointmentHistory->links() }}</div>
        </div>
    </x-slot>
</x-layouts.admin>
