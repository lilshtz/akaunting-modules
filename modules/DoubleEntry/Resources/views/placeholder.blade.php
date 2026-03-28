<x-layouts.admin>
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <x-slot name="content">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
        </div>
    </x-slot>
</x-layouts.admin>
