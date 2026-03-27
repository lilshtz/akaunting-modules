<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.appointments') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.create') }}" kind="primary">{{ trans('general.title.new', ['type' => trans('appointments::general.appointment')]) }}</x-link>
        <x-link href="{{ route('appointments.reminders.send') }}">{{ trans('appointments::general.send_reminders') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <form method="GET" action="{{ route('appointments.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.date') }}</label>
                        <input type="date" name="date" value="{{ request('date', $selectedDate->toDateString()) }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.calendar') }}</label>
                        <select name="view" class="w-full rounded-lg border-gray-300">
                            <option value="month" @selected($view === 'month')>{{ trans('appointments::general.monthly') }}</option>
                            <option value="week" @selected($view === 'week')>{{ trans('appointments::general.weekly') }}</option>
                            <option value="day" @selected($view === 'day')>{{ trans('appointments::general.daily') }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end gap-3">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.filter') }}</button>
                        <a href="{{ route('appointments.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.clear') }}</a>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($days as $day)
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="font-semibold">{{ $day['date']->format('M d, Y') }}</div>
                        <div class="mt-3 space-y-2">
                            @forelse ($day['appointments'] as $appointment)
                                <div class="rounded-lg border border-purple-100 bg-purple-50 px-3 py-2">
                                    <a href="{{ route('appointments.show', $appointment->id) }}" class="font-medium text-purple-700 hover:underline">
                                        {{ $appointment->start_time }} - {{ $appointment->end_time }}
                                    </a>
                                    <div class="text-sm text-gray-600">{{ $appointment->customer_name }} @if($appointment->location) · {{ $appointment->location }} @endif</div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-400">{{ trans('general.no_records') }}</div>
                            @endforelse

                            @foreach ($day['leave'] as $leave)
                                <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-sm">
                                    {{ $leave->employee?->name }} · {{ $leave->type_label }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.date') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.customer') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.assigned_user') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            <tr class="border-b">
                                <td class="px-4 py-3 text-sm">{{ $appointment->date?->format('M d, Y') }} {{ $appointment->start_time }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->customer_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->user?->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $appointment->status_label }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('appointments.show', $appointment->id) }}" class="text-purple-700 hover:underline mr-3">{{ trans('general.show') }}</a>
                                    <a href="{{ route('appointments.edit', $appointment->id) }}" class="text-purple-700 hover:underline">{{ trans('general.edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
