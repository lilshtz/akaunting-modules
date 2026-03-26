<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.edit') . ' ' . trans('projects::general.milestone') }}
    </x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('projects.milestones.update', $milestone->id) }}" class="rounded-2xl bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $milestone->name) }}" required class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.target_date') }}</label>
                    <input type="date" name="target_date" value="{{ old('target_date', $milestone->target_date?->toDateString()) }}" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.completed_at') }}</label>
                    <input type="datetime-local" name="completed_at" value="{{ old('completed_at', $milestone->completed_at?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.position') }}</label>
                    <input type="number" name="position" value="{{ old('position', $milestone->position) }}" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description', $milestone->description) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-link href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => 'milestones']) }}">
                    {{ trans('general.cancel') }}
                </x-link>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
