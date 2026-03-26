<x-layouts.admin>
    <x-slot name="title">
        {{ $calendar ? trans('general.title.edit', ['type' => trans('payroll::general.pay_calendar')]) : trans('general.title.new', ['type' => trans('payroll::general.pay_calendar')]) }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ $route }}" class="space-y-4">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $calendar?->name) }}" class="w-full rounded-lg border-gray-300" required />
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.frequency') }}</label>
                        <select name="frequency" class="w-full rounded-lg border-gray-300" required>
                            @foreach (['weekly', 'biweekly', 'monthly', 'custom'] as $frequency)
                                <option value="{{ $frequency }}" @selected(old('frequency', $calendar?->frequency) === $frequency)>{{ trans('payroll::general.frequencies.' . $frequency) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.start_date') }}</label>
                        <input type="date" name="start_date" value="{{ old('start_date', optional($calendar?->start_date)->toDateString()) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.next_run_date') }}</label>
                        <input type="date" name="next_run_date" value="{{ old('next_run_date', optional($calendar?->next_run_date)->toDateString()) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $calendar?->enabled ?? true)) />
                    <span>{{ trans('general.enabled') }}</span>
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ trans('payroll::general.assigned_employees') }}</label>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($employees as $employee)
                            <label class="flex items-center gap-2 p-3 border rounded-lg">
                                <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" @checked(in_array($employee->id, old('employee_ids', $selectedEmployeeIds), true)) />
                                <span>{{ $employee->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    <a href="{{ route('payroll.pay-calendars.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
