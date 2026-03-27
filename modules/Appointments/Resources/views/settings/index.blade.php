<x-layouts.admin>
    <x-slot name="title">{{ trans('appointments::general.settings') }}</x-slot>

    <x-slot name="content">
        <div class="max-w-4xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('appointments.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach ($leaveTypes as $type => $label)
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ ucfirst($type) }} {{ trans('general.name') }}</label>
                            <input type="text" name="leave_types[{{ $type }}]" value="{{ old('leave_types.' . $type, $label) }}" class="w-full rounded-lg border-gray-300" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ ucfirst($type) }} {{ trans('appointments::general.allowance') }}</label>
                            <input type="number" step="0.01" min="0" name="leave_allowances[{{ $type }}]" value="{{ old('leave_allowances.' . $type, $leaveAllowances[$type] ?? 0) }}" class="w-full rounded-lg border-gray-300" required />
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
