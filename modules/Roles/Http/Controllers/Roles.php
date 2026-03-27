<?php

namespace Modules\Roles\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Roles\Http\Requests\RoleStore;
use Modules\Roles\Http\Requests\RoleUpdate;
use Modules\Roles\Models\Role;
use Modules\Roles\Services\ModuleCatalog;
use Modules\Roles\Services\PermissionResolver;
use Modules\Roles\Services\RoleTemplateService;

class Roles extends Controller
{
    public function __construct(
        protected PermissionResolver $resolver,
        protected ModuleCatalog $catalog,
        protected RoleTemplateService $templates
    ) {
    }

    public function index(): Response|mixed
    {
        $companyId = company_id();

        $this->templates->ensureDefaults($companyId);

        $roles = $this->queryForCompany($companyId)
            ->withCount([
                'userCompanyRoles as assigned_users_count' => fn ($query) => $query->where('company_id', $companyId),
            ])
            ->orderBy('display_name')
            ->get();

        return view('roles::roles.index', compact('roles'));
    }

    public function create(Request $request): Response|mixed
    {
        $companyId = company_id();
        $template = $request->get('template', 'manager');
        $role = new Role();

        return view('roles::roles.create', $this->formData($role, $companyId, $template));
    }

    public function store(RoleStore $request): Response|mixed
    {
        $companyId = company_id();
        $template = $request->get('template', 'custom');
        $displayName = $request->get('display_name');

        $role = Role::create([
            'name' => $this->generateSystemName($displayName, $companyId),
            'display_name' => $displayName,
            'description' => $request->get('description'),
        ]);

        $this->resolver->syncRolePermissions(
            $role,
            $companyId,
            $this->normalizePermissions($request->input('permissions', []), $template)
        );

        flash(trans('messages.success.added', ['type' => trans('roles::general.role')]))->success();

        return redirect()->route('roles.roles.edit', $role->id);
    }

    public function edit(int $id): Response|mixed
    {
        $companyId = company_id();
        $role = $this->findRole($id, $companyId);

        return view('roles::roles.edit', $this->formData($role, $companyId));
    }

    public function update(int $id, RoleUpdate $request): Response|mixed
    {
        $companyId = company_id();
        $role = $this->findRole($id, $companyId);
        $template = $request->get('template', 'custom');

        $role->update([
            'display_name' => $request->get('display_name'),
            'description' => $request->get('description'),
        ]);

        $this->resolver->syncRolePermissions(
            $role,
            $companyId,
            $this->normalizePermissions($request->input('permissions', []), $template)
        );

        flash(trans('messages.success.updated', ['type' => trans('roles::general.role')]))->success();

        return redirect()->route('roles.roles.edit', $role->id);
    }

    public function duplicate(int $id): Response|mixed
    {
        $companyId = company_id();
        $role = $this->findRole($id, $companyId);

        $copy = Role::create([
            'name' => $this->generateSystemName($role->display_name . '-copy', $companyId),
            'display_name' => $role->display_name . ' Copy',
            'description' => $role->description,
        ]);

        if (method_exists($copy, 'permissions')) {
            $copy->permissions()->sync($role->permissions()->pluck('id')->all());
        }

        $this->resolver->syncRolePermissions(
            $copy,
            $companyId,
            $this->resolver->permissionsForRole($role, $companyId)
                ->mapWithKeys(fn ($permission) => [
                    $permission->module_alias => [
                        'can_view' => $permission->can_view,
                        'can_create' => $permission->can_create,
                        'can_edit' => $permission->can_edit,
                        'can_delete' => $permission->can_delete,
                    ],
                ])
                ->all()
        );

        flash(trans('roles::general.messages.cloned'))->success();

        return redirect()->route('roles.roles.edit', $copy->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $companyId = company_id();
        $role = $this->findRole($id, $companyId);

        if (Str::lower((string) $role->name) === 'admin') {
            abort(422, trans('roles::general.messages.cannot_delete_admin'));
        }

        $role->modulePermissions()->where('company_id', $companyId)->delete();
        $role->userCompanyRoles()->where('company_id', $companyId)->delete();

        if (! $role->modulePermissions()->exists() && ! $role->userCompanyRoles()->exists() && ! Str::startsWith((string) $role->name, 'module-')) {
            $role->delete();
        }

        flash(trans('messages.success.deleted', ['type' => trans('roles::general.role')]))->success();

        return redirect()->route('roles.roles.index');
    }

    protected function formData(Role $role, int $companyId, ?string $template = 'custom'): array
    {
        $modules = $this->catalog->all();
        $templates = collect($this->templates->definitions())
            ->mapWithKeys(fn ($definition, $key) => [$key => $definition['display_name']]);
        $templatePermissions = collect(['manager', 'accountant', 'employee', 'customer'])
            ->mapWithKeys(fn ($name) => [$name => $this->templates->permissionsForTemplate($name)])
            ->all();
        $permissions = $this->permissionRows($role, $companyId, $template);

        return compact('role', 'modules', 'templates', 'templatePermissions', 'permissions', 'template');
    }

    protected function permissionRows(Role $role, int $companyId, ?string $template): array
    {
        $stored = $this->resolver->permissionsForRole($role, $companyId)
            ->mapWithKeys(fn ($permission) => [
                $permission->module_alias => [
                    'can_view' => $permission->can_view,
                    'can_create' => $permission->can_create,
                    'can_edit' => $permission->can_edit,
                    'can_delete' => $permission->can_delete,
                ],
            ])
            ->all();

        if (! empty($stored)) {
            return $stored;
        }

        return $this->templates->permissionsForTemplate($template);
    }

    protected function normalizePermissions(array $permissions, ?string $template): array
    {
        if (! empty($permissions)) {
            return collect($permissions)->mapWithKeys(function ($values, $alias) {
                return [
                    $alias => [
                        'can_view' => (bool) ($values['can_view'] ?? false),
                        'can_create' => (bool) ($values['can_create'] ?? false),
                        'can_edit' => (bool) ($values['can_edit'] ?? false),
                        'can_delete' => (bool) ($values['can_delete'] ?? false),
                    ],
                ];
            })->all();
        }

        return $this->templates->permissionsForTemplate($template);
    }

    protected function queryForCompany(int $companyId)
    {
        return Role::query()->where(function ($query) use ($companyId) {
            $query->whereHas('modulePermissions', fn ($sub) => $sub->where('company_id', $companyId))
                ->orWhereHas('userCompanyRoles', fn ($sub) => $sub->where('company_id', $companyId))
                ->orWhereIn('name', ['admin', 'module-manager', 'module-accountant', 'module-employee', 'module-customer']);
        });
    }

    protected function findRole(int $id, int $companyId): Role
    {
        return $this->queryForCompany($companyId)->findOrFail($id);
    }

    protected function generateSystemName(string $displayName, int $companyId): string
    {
        return Str::slug($displayName, '-') . '-' . $companyId . '-' . Str::lower(Str::random(6));
    }
}
