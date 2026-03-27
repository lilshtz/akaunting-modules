<x-layouts.admin>
    <x-slot name="title">{{ $form->name }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.forms.edit', $form->id) }}">{{ trans('general.edit') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt>{{ trans('general.name') }}</dt><dd>{{ $form->name }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('general.enabled') }}</dt><dd>{{ $form->enabled ? trans('general.yes') : trans('general.no') }}</dd></div>
                </dl>

                <div class="mt-4">
                    <div class="text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.booking_link') }}</div>
                    <div class="text-sm break-all">{{ $form->booking_url }}</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-3">{{ trans('appointments::general.public_booking') }}</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    @forelse ($form->fields_json ?? [] as $field)
                        <li>{{ $field['label'] }} ({{ $field['type'] }}) @if($field['required']) * @endif</li>
                    @empty
                        <li>{{ trans('general.no_records') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
