<x-layouts.admin>
    <x-slot name="title">{{ trans('payroll::general.run_payroll') }}</x-slot>

    <x-slot name="content">
        <div class="max-w-3xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('payroll.payroll-runs.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.pay_calendar') }}</label>
                    <select name="pay_calendar_id" class="w-full rounded-lg border-gray-300" required>
                        <option value="">{{ trans('general.form.select.field', ['field' => trans('payroll::general.pay_calendar')]) }}</option>
                        @foreach ($calendars as $calendar)
                            <option value="{{ $calendar->id }}" @selected((string) old('pay_calendar_id', $selectedCalendarId) === (string) $calendar->id)>{{ $calendar->name }} ({{ $calendar->employees_count }} {{ strtolower(trans('payroll::general.employees')) }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('payroll::general.review_run') }}</button>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
