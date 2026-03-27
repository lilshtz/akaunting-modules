<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.appointment') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('appointments.edit', $appointment->id) }}">{{ trans('general.edit') }}</x-link>
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt>{{ trans('general.date') }}</dt><dd>{{ $appointment->date?->format('M d, Y') }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.customer') }}</dt><dd>{{ $appointment->customer_name }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.assigned_user') }}</dt><dd>{{ $appointment->user?->name }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.location') }}</dt><dd>{{ $appointment->location ?: trans('general.na') }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('general.status') }}</dt><dd>{{ $appointment->status_label }}</dd></div>
                    <div class="flex justify-between"><dt>{{ trans('appointments::general.reminder_sent') }}</dt><dd>{{ $appointment->reminder_sent ? trans('general.yes') : trans('general.no') }}</dd></div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-3">{{ trans('appointments::general.notes') }}</h3>
                <div class="text-sm text-gray-600 whitespace-pre-line">{{ $appointment->notes ?: trans('general.na') }}</div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
