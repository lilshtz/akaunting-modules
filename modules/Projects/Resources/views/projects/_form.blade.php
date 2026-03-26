<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
        <input type="text" name="name" value="{{ old('name', $project->name ?? '') }}" required class="w-full rounded-lg border-gray-300 text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.client') }}</label>
        <select name="contact_id" class="w-full rounded-lg border-gray-300 text-sm">
            @foreach ($contacts as $id => $name)
                <option value="{{ $id }}" {{ (string) old('contact_id', $project->contact_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
        <textarea name="description" rows="4" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description', $project->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
        <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" {{ old('status', $project->status ?? 'active') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.billing_type') }}</label>
        <select name="billing_type" class="w-full rounded-lg border-gray-300 text-sm">
            @foreach ($billingTypes as $key => $label)
                <option value="{{ $key }}" {{ old('billing_type', $project->billing_type ?? 'fixed_rate') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.billing_rate') }}</label>
        <input type="number" name="billing_rate" step="0.0001" min="0" value="{{ old('billing_rate', $project->billing_rate ?? '') }}" class="w-full rounded-lg border-gray-300 text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.budget') }}</label>
        <input type="number" name="budget" step="0.0001" min="0" value="{{ old('budget', $project->budget ?? '') }}" class="w-full rounded-lg border-gray-300 text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.start_date') }}</label>
        <input type="date" name="start_date" value="{{ old('start_date', isset($project) && $project->start_date ? $project->start_date->toDateString() : '') }}" class="w-full rounded-lg border-gray-300 text-sm" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.end_date') }}</label>
        <input type="date" name="end_date" value="{{ old('end_date', isset($project) && $project->end_date ? $project->end_date->toDateString() : '') }}" class="w-full rounded-lg border-gray-300 text-sm" />
    </div>
</div>

<div class="mt-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ trans('projects::general.team_members') }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($users as $id => $name)
            <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3">
                <span class="flex items-center gap-3">
                    <input type="checkbox" name="member_ids[]" value="{{ $id }}" {{ array_key_exists($id, old('member_roles', $selectedMembers)) || in_array($id, old('member_ids', array_keys($selectedMembers)), true) ? 'checked' : '' }} />
                    <span class="text-sm">{{ $name }}</span>
                </span>
                <select name="member_roles[{{ $id }}]" class="rounded-lg border-gray-300 text-sm">
                    @foreach ($memberRoles as $role => $label)
                        <option value="{{ $role }}" {{ old('member_roles.' . $id, $selectedMembers[$id] ?? 'member') === $role ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
    </div>
</div>
