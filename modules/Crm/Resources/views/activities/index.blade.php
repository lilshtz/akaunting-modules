<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.activities') }}</x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            @forelse ($activities as $activity)
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-medium">{{ trans('crm::general.activity_types.' . $activity->type) }}</div>
                            <div class="text-sm text-gray-500">
                                <a href="{{ route('crm.contacts.show', $activity->contact?->id) }}" class="text-sky-700">{{ $activity->contact?->name ?? '-' }}</a>
                                • {{ $activity->user?->name ?? '-' }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">{{ optional($activity->created_at)->format('M d, Y H:i') }}</div>
                    </div>
                    <div class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $activity->description }}</div>
                </div>
            @empty
                <div class="rounded-xl bg-white p-6 text-sm text-gray-500 shadow-sm">{{ trans('crm::general.activities') }}: 0</div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="mt-4">{{ $activities->withQueryString()->links() }}</div>
        @endif
    </x-slot>
</x-layouts.admin>
