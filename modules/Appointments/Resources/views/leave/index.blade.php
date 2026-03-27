<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.leave') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.leave.create') }}" kind="primary">{{ trans('appointments::general.request_leave') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <form method="GET" action="{{ route('appointments.leave.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('employees::general.employee') }}</label>
                        <select name="employee_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.all') }}</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) request('employee_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                        <select name="status" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.all') }}</option>
                            @foreach ($statuses as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.year') }}</label>
                        <input type="number" name="year" value="{{ request('year', now()->year) }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.filter') }}</button>
                        <a href="{{ route('appointments.leave.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.clear') }}</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('employees::general.employee') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.type') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.date_range') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('appointments::general.days') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $leave)
                            <tr class="border-b">
                                <td class="px-4 py-3 text-sm">{{ $leave->employee?->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $types[$leave->type] ?? $leave->type }}</td>
                                <td class="px-4 py-3 text-sm">{{ $leave->start_date?->format('M d, Y') }} - {{ $leave->end_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $leave->days }}</td>
                                <td class="px-4 py-3 text-sm">{{ $leave->status_label }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('appointments.leave.show', $leave->id) }}" class="text-purple-700 hover:underline mr-3">{{ trans('general.show') }}</a>
                                    <a href="{{ route('appointments.leave.edit', $leave->id) }}" class="text-purple-700 hover:underline">{{ trans('general.edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $requests->links() }}</div>
        </div>
    </x-slot>
</x-layouts.admin>
