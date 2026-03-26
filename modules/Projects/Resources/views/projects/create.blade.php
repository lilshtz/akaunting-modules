<x-layouts.admin>
    <x-slot name="title">
        {{ trans('projects::general.new_project') }}
    </x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('projects.projects.store') }}" class="rounded-2xl bg-white p-6 shadow-sm">
            @csrf

            @include('projects::projects._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-link href="{{ route('projects.projects.index') }}">
                    {{ trans('general.cancel') }}
                </x-link>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">
                    {{ trans('general.save') }}
                </button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
