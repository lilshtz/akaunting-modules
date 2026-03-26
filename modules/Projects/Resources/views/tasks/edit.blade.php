<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.edit') . ' ' . trans('projects::general.task') }}
    </x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('projects.tasks.update', $task->id) }}" class="rounded-2xl bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $task->name) }}" required class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.milestone') }}</label>
                    <select name="milestone_id" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($milestonesForSelect as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('milestone_id', $task->milestone_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.assignee') }}</label>
                    <select name="assignee_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ trans('general.none') }}</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('assignee_id', $task->assignee_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.priority') }}</label>
                    <select name="priority" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($priorities as $key => $label)
                            <option value="{{ $key }}" {{ old('priority', $task->priority) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($taskStatuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $task->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.estimated_hours') }}</label>
                    <input type="number" name="estimated_hours" step="0.0001" min="0" value="{{ old('estimated_hours', $task->estimated_hours) }}" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.position') }}</label>
                    <input type="number" name="position" value="{{ old('position', $task->position) }}" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description', $task->description) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-link href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']) }}">
                    {{ trans('general.cancel') }}
                </x-link>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
