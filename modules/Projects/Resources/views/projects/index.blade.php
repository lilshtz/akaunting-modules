<x-layouts.admin>
    <x-slot name="title">
        {{ trans('projects::general.project_list') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('projects::general.name') }}"
        icon="construction"
        route="projects.projects.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('projects.projects.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('projects::general.project')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="mb-4 flex flex-wrap gap-3">
            <form method="GET" action="{{ route('projects.projects.index') }}" class="flex flex-wrap gap-3 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('general.search') }}..." class="rounded-lg border-gray-300 text-sm px-3 py-2" />

                <select name="status" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('general.status') }}</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="contact_id" class="rounded-lg border-gray-300 text-sm px-3 py-2" onchange="this.form.submit()">
                    <option value="">{{ trans('projects::general.client') }}</option>
                    @foreach ($contacts as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('contact_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">
                    {{ trans('general.search') }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @forelse ($projects as $project)
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <a href="{{ route('projects.projects.show', $project->id) }}" class="text-lg font-semibold text-slate-900 hover:text-blue-700">
                                {{ $project->name }}
                            </a>
                            <p class="mt-1 text-sm text-gray-500">{{ $project->contact?->name ?? '-' }}</p>
                        </div>

                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                            {{ $project->status_label }}
                        </span>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 line-clamp-3">{{ $project->description ?: trans('projects::general.empty_state') }}</p>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">{{ trans('projects::general.budget') }}</span>
                            <span class="font-medium text-slate-900">{{ $project->budget_display }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">{{ trans('projects::general.billing_type') }}</span>
                            <span class="font-medium text-slate-900">{{ $project->billing_type_label }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">{{ trans('projects::general.progress') }}</span>
                            <span class="font-medium text-slate-900">{{ $project->progress_percentage }}%</span>
                        </div>
                    </div>

                    <div class="mt-4 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $project->progress_percentage }}%"></div>
                    </div>

                    <div class="mt-5 flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ $project->tasks->count() }} {{ trans('projects::general.tasks') }}</span>
                        <a href="{{ route('projects.projects.show', $project->id) }}" class="font-medium text-blue-700 hover:underline">
                            {{ trans('general.show') }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="xl:col-span-2 rounded-2xl bg-white p-8 text-center text-gray-500 shadow-sm">
                    {{ trans('projects::general.empty_state') }}
                </div>
            @endforelse
        </div>

        @if ($projects->hasPages())
            <div class="mt-4">
                {{ $projects->withQueryString()->links() }}
            </div>
        @endif
    </x-slot>
</x-layouts.admin>
