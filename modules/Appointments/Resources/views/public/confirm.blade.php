<x-layouts.signed>
    <x-slot name="title">{{ trans('appointments::general.booked') }}</x-slot>

    <x-slot name="content">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-2xl font-semibold mb-4">{{ trans('appointments::general.booked') }}</h1>
            <p class="text-gray-600">{{ trans('appointments::general.messages.appointment_booked') }}</p>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between"><dt>{{ trans('general.date') }}</dt><dd>{{ $appointment->date?->format('M d, Y') }}</dd></div>
                <div class="flex justify-between"><dt>{{ trans('general.start_time') }}</dt><dd>{{ $appointment->start_time }}</dd></div>
                <div class="flex justify-between"><dt>{{ trans('general.end_time') }}</dt><dd>{{ $appointment->end_time }}</dd></div>
                <div class="flex justify-between"><dt>{{ trans('appointments::general.location') }}</dt><dd>{{ $appointment->location ?: trans('general.na') }}</dd></div>
            </dl>
        </div>
    </x-slot>
</x-layouts.signed>
