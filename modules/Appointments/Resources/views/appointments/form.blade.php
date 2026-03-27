<x-layouts.admin>
    <x-slot name="title">
        {{ $appointment ? trans('general.title.edit', ['type' => trans('appointments::general.appointment')]) : trans('general.title.new', ['type' => trans('appointments::general.appointment')]) }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ $route }}" class="space-y-4">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.customer') }}</label>
                        <select name="contact_id" class="w-full rounded-lg border-gray-300">
                            <option value="">{{ trans('general.na') }}</option>
                            @foreach ($contacts as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('contact_id', $appointment?->contact_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.assigned_user') }}</label>
                        <select name="user_id" class="w-full rounded-lg border-gray-300" required>
                            <option value="">{{ trans('general.form.select.field', ['field' => trans('appointments::general.assigned_user')]) }}</option>
                            @foreach ($users as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('user_id', $appointment?->user_id) === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.date') }}</label>
                        <input type="date" name="date" value="{{ old('date', optional($appointment?->date)->toDateString()) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.start_time') }}</label>
                        <input type="time" name="start_time" value="{{ old('start_time', $appointment?->start_time) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.end_time') }}</label>
                        <input type="time" name="end_time" value="{{ old('end_time', $appointment?->end_time) }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.location') }}</label>
                        <input type="text" name="location" value="{{ old('location', $appointment?->location) }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                        <select name="status" class="w-full rounded-lg border-gray-300">
                            @foreach ($statuses as $status => $label)
                                <option value="{{ $status }}" @selected(old('status', $appointment?->status ?? 'scheduled') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.notes') }}</label>
                    <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300">{{ old('notes', $appointment?->notes) }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    <a href="{{ route('appointments.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
