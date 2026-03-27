<x-layouts.admin>
    <x-slot name="title">
        {{ $leave ? trans('general.title.edit', ['type' => trans('appointments::general.leave_request')]) : trans('general.title.new', ['type' => trans('appointments::general.leave_request')]) }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ $route }}" class="space-y-4">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('employees::general.employee') }}</label>
                        <select name="employee_id" class="w-full rounded-lg border-gray-300" required>
                            <option value="">{{ trans('general.form.select.field', ['field' => trans('employees::general.employee')]) }}</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('employee_id', $leave?->employee_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.approver') }}</label>
                        <select name="approver_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.na') }}</option>
                            @foreach ($approvers as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('approver_id', $leave?->approver_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.type') }}</label>
                        <select name="type" class="w-full rounded-lg border-gray-300" required>
                            @foreach ($types as $type => $label)
                                <option value="{{ $type }}" @selected(old('type', $leave?->type ?? 'vacation') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.start_date') }}</label>
                        <input type="date" name="start_date" value="{{ old('start_date', optional($leave?->start_date)->toDateString()) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.end_date') }}</label>
                        <input type="date" name="end_date" value="{{ old('end_date', optional($leave?->end_date)->toDateString()) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.reason') }}</label>
                    <textarea name="reason" rows="4" class="w-full rounded-lg border-gray-300">{{ old('reason', $leave?->reason) }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    <a href="{{ route('appointments.leave.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
