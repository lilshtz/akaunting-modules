<x-layouts.admin>
    <x-slot name="title">
        {{ trans('projects::general.pnl_report') . ': ' . $project->name }}
    </x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => 'budget']) }}">
            {{ trans('projects::general.budget_dashboard') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="mb-6 rounded-3xl bg-slate-900 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-300">{{ trans('projects::general.pnl_report') }}</p>
                    <h2 class="mt-2 text-3xl font-semibold">{{ $project->name }}</h2>
                    <p class="mt-2 text-sm text-slate-300">{{ $project->contact?->name ?? trans('general.na') }}</p>
                </div>
                <div class="text-sm text-slate-300">
                    <a href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => 'overview']) }}" class="text-white hover:underline">
                        {{ trans('general.back') }}
                    </a>
                </div>
            </div>
        </div>

        @include('projects::projects.partials.financial-dashboard', ['showReportLink' => false])
    </x-slot>
</x-layouts.admin>
