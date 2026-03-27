@php
    $permissionRows = old('permissions', $permissions ?? []);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('general.name') }}</label>
        <input type="text" name="display_name" value="{{ old('display_name', $role->display_name) }}" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ trans('roles::general.template') }}</label>
        <select name="template" id="role-template" class="form-control">
            @foreach($templates as $value => $label)
                <option value="{{ $value }}" @selected(old('template', $template) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">{{ trans('roles::general.apply_template') }}</small>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">{{ trans('general.description') }}</label>
    <textarea name="description" rows="3" class="form-control">{{ old('description', $role->description) }}</textarea>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="mb-0">{{ trans('roles::general.permission_matrix') }}</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                <tr>
                    <th>{{ trans('roles::general.module') }}</th>
                    <th class="text-center">{{ trans('roles::general.view') }}</th>
                    <th class="text-center">{{ trans('roles::general.create') }}</th>
                    <th class="text-center">{{ trans('roles::general.edit') }}</th>
                    <th class="text-center">{{ trans('roles::general.delete') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($modules as $module)
                    @php
                        $values = $permissionRows[$module['alias']] ?? [];
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $module['title'] }}</strong>
                            <div class="text-muted small">{{ $module['alias'] }}</div>
                        </td>
                        <td class="text-center"><input type="checkbox" name="permissions[{{ $module['alias'] }}][can_view]" value="1" @checked((bool) ($values['can_view'] ?? false))></td>
                        <td class="text-center"><input type="checkbox" name="permissions[{{ $module['alias'] }}][can_create]" value="1" @checked((bool) ($values['can_create'] ?? false))></td>
                        <td class="text-center"><input type="checkbox" name="permissions[{{ $module['alias'] }}][can_edit]" value="1" @checked((bool) ($values['can_edit'] ?? false))></td>
                        <td class="text-center"><input type="checkbox" name="permissions[{{ $module['alias'] }}][can_delete]" value="1" @checked((bool) ($values['can_delete'] ?? false))></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ trans('roles::general.save_role') }}</button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const templateSelect = document.getElementById('role-template');
        const templates = @json($templatePermissions);

        templateSelect?.addEventListener('change', function () {
            const values = templates[this.value];

            if (!values) {
                return;
            }

            Object.entries(values).forEach(([alias, actions]) => {
                Object.entries(actions).forEach(([action, allowed]) => {
                    const field = document.querySelector(`input[name="permissions[${alias}][${action}]"]`);
                    if (field) {
                        field.checked = !!allowed;
                    }
                });
            });
        });
    });
</script>
