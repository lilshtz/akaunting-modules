<x-layouts.signed>
    <x-slot name="title">{{ $form->name }}</x-slot>

    <x-slot name="content">
        <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-2xl font-semibold mb-6">{{ $form->name }}</h1>

            <form method="POST" action="{{ route('signed.appointments.booking.store', $form->public_link) }}" class="space-y-4">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.date') }}</label>
                        <input type="date" name="date" value="{{ old('date') }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.start_time') }}</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.end_time') }}</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full rounded-lg border-gray-300" required />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.location') }}</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="w-full rounded-lg border-gray-300" />
                </div>

                @foreach ($form->fields_json ?? [] as $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $field['label'] }}</label>
                        @if (($field['type'] ?? 'text') === 'textarea')
                            <textarea name="custom[{{ $field['name'] }}]" rows="3" class="w-full rounded-lg border-gray-300" @required($field['required'])>{{ old('custom.' . $field['name']) }}</textarea>
                        @else
                            <input type="{{ $field['type'] === 'phone' ? 'text' : $field['type'] }}" name="custom[{{ $field['name'] }}]" value="{{ old('custom.' . $field['name']) }}" class="w-full rounded-lg border-gray-300" @required($field['required']) />
                        @endif
                    </div>
                @endforeach

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('appointments::general.notes') }}</label>
                    <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('appointments::general.book_appointment') }}</button>
            </form>
        </div>
    </x-slot>
</x-layouts.signed>
