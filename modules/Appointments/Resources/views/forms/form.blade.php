@php
    $fieldLines = old('fields_json');

    if ($fieldLines === null) {
        $fieldLines = collect($form?->fields_json ?? [])->map(function ($field) {
            $parts = [$field['label'] ?? ''];

            if (! empty($field['type']) && $field['type'] !== 'text') {
                $parts[] = $field['type'];
            }

            if (! empty($field['required'])) {
                $parts[] = 'required';
            }

            return implode('|', $parts);
        })->implode(PHP_EOL);
    }
@endphp

<x-layouts.admin>
    <x-slot name="title">
        {{ $form ? trans('general.title.edit', ['type' => trans('appointments::general.form')]) : trans('general.title.new', ['type' => trans('appointments::general.form')]) }}
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
                    <input type="text" name="name" value="{{ old('name', $form?->name) }}" class="w-full rounded-lg border-gray-300" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.public_booking') }}</label>
                    <textarea name="fields_json" rows="6" class="w-full rounded-lg border-gray-300">{{ $fieldLines }}</textarea>
                    <p class="mt-2 text-sm text-gray-500">{{ trans('appointments::general.custom_fields_help') }}</p>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $form?->enabled ?? true)) />
                    <span>{{ trans('general.enabled') }}</span>
                </label>

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    <a href="{{ route('appointments.forms.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
