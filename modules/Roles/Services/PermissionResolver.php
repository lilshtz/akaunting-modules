<?php

namespace Modules\Roles\Services;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Roles\Models\Role;
use Modules\Roles\Models\RoleModulePermission;
use Modules\Roles\Models\UserCompanyRole;

class PermissionResolver
{
    public function resolveAbility($user, string $ability): ?bool
    {
        $companyId = $this->companyId();

        if (! $user || ! $companyId || $this->isAdmin($user)) {
            return null;
        }

        $action = $this->mapAbilityToAction($ability);

        if (! $action) {
            return null;
        }

        $assignment = $this->assignmentForUser((int) $user->id, $companyId);

        if (! $assignment) {
            return null;
        }

        return $this->resolveFromCandidates($assignment->role, $companyId, $this->candidatesFromAbility($ability), $action);
    }

    public function resolveRequest(Request $request): ?bool
    {
        $user = $request->user();
        $companyId = $this->companyId();

        if (! $user || ! $companyId || $this->isAdmin($user)) {
            return null;
        }

        $assignment = $this->assignmentForUser((int) $user->id, $companyId);

        if (! $assignment) {
            return null;
        }

        $action = $this->actionFromRoute($request->route(), $request);
        $candidates = $this->candidatesFromRoute($request->route(), $request);

        if (empty($candidates) || ! $action) {
            return null;
        }

        return $this->resolveFromCandidates($assignment->role, $companyId, $candidates, $action);
    }

    public function permissionsForRole(Role $role, int $companyId): Collection
    {
        return $role->modulePermissions()
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('module_alias');
    }

    public function syncRolePermissions(Role $role, int $companyId, array $permissions): void
    {
        $keep = [];

        foreach ($permissions as $moduleAlias => $values) {
            $moduleAlias = Str::kebab((string) $moduleAlias);
            $keep[] = $moduleAlias;

            RoleModulePermission::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'role_id' => $role->id,
                    'module_alias' => $moduleAlias,
                ],
                [
                    'can_view' => (bool) ($values['can_view'] ?? false),
                    'can_create' => (bool) ($values['can_create'] ?? false),
                    'can_edit' => (bool) ($values['can_edit'] ?? false),
                    'can_delete' => (bool) ($values['can_delete'] ?? false),
                ]
            );
        }

        $role->modulePermissions()
            ->where('company_id', $companyId)
            ->when(! empty($keep), fn ($query) => $query->whereNotIn('module_alias', $keep))
            ->delete();
    }

    public function assignRoleToUser(int $userId, int $roleId, int $companyId): UserCompanyRole
    {
        return UserCompanyRole::updateOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId],
            ['role_id' => $roleId]
        );
    }

    public function removeAssignment(int $userId, int $companyId): void
    {
        UserCompanyRole::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function assignmentForUser(int $userId, int $companyId): ?UserCompanyRole
    {
        return UserCompanyRole::with('role')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();
    }

    protected function resolveFromCandidates(Role $role, int $companyId, array $candidates, string $action): ?bool
    {
        $candidates = collect($candidates)
            ->map(fn ($candidate) => Str::kebab($candidate))
            ->unique()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $permission = RoleModulePermission::where('company_id', $companyId)
            ->where('role_id', $role->id)
            ->whereIn('module_alias', $candidates)
            ->get()
            ->sortBy(fn ($item) => $candidates->search($item->module_alias))
            ->first();

        if (! $permission) {
            return null;
        }

        return match ($action) {
            'view' => (bool) $permission->can_view,
            'create' => (bool) $permission->can_create,
            'edit' => (bool) $permission->can_edit,
            'delete' => (bool) $permission->can_delete,
            default => null,
        };
    }

    protected function candidatesFromAbility(string $ability): array
    {
        $parts = explode('-', Str::kebab($ability));

        if (count($parts) < 2) {
            return [];
        }

        array_shift($parts);

        return $this->expandCandidates($parts);
    }

    protected function candidatesFromRoute(?IlluminateRoute $route, Request $request): array
    {
        $candidates = [];

        if ($route?->getName()) {
            $nameParts = explode('.', $route->getName());

            if (($nameParts[0] ?? null) === 'api') {
                $nameParts = array_slice($nameParts, 1);
            }

            if (! empty($nameParts[0])) {
                $candidates[] = $nameParts[0];
            }

            if (! empty($nameParts[1])) {
                $candidates[] = $nameParts[1];
            }
        }

        if ($route?->uri()) {
            $uriParts = collect(explode('/', trim($route->uri(), '/')))
                ->reject(fn ($part) => in_array($part, ['api', 'portal', 'signed'], true) || Str::startsWith($part, '{'))
                ->values()
                ->all();

            if (! empty($uriParts[0])) {
                $candidates[] = $uriParts[0];
            }

            if (! empty($uriParts[1])) {
                $candidates[] = $uriParts[1];
            }
        }

        if ($controller = $route?->getActionName()) {
            if (preg_match('/Modules\\\\([^\\\\]+)\\\\/', $controller, $matches)) {
                $candidates[] = Str::kebab($matches[1]);
            }
        }

        return $this->expandCandidates($candidates);
    }

    protected function expandCandidates(array $parts): array
    {
        $flattened = collect($parts)
            ->flatMap(function ($part) {
                return preg_split('/[-_]/', (string) $part) ?: [];
            })
            ->filter()
            ->values()
            ->all();

        $candidates = collect($parts)
            ->map(fn ($part) => Str::kebab((string) $part))
            ->filter()
            ->values()
            ->all();

        $count = count($flattened);

        for ($length = $count; $length >= 1; $length--) {
            for ($offset = 0; $offset <= $count - $length; $offset++) {
                $candidates[] = implode('-', array_slice($flattened, $offset, $length));
            }
        }

        return collect($candidates)->unique()->values()->all();
    }

    protected function actionFromRoute(?IlluminateRoute $route, Request $request): ?string
    {
        $name = $route?->getName() ?: '';
        $last = Str::afterLast($name, '.');

        if (in_array($last, ['create', 'store'], true)) {
            return 'create';
        }

        if (in_array($last, ['edit', 'update', 'duplicate'], true)) {
            return 'edit';
        }

        if (in_array($last, ['destroy', 'delete'], true)) {
            return 'delete';
        }

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    protected function mapAbilityToAction(string $ability): ?string
    {
        return match (Str::before($ability, '-')) {
            'read', 'view' => 'view',
            'create', 'store' => 'create',
            'update', 'edit' => 'edit',
            'delete', 'destroy' => 'delete',
            default => null,
        };
    }

    protected function isAdmin($user): bool
    {
        if (! method_exists($user, 'roles')) {
            return false;
        }

        return $user->roles->contains(function ($role) {
            $name = Str::lower((string) $role->name);
            $displayName = Str::lower((string) $role->display_name);

            return in_array($name, ['admin', 'administrator'], true)
                || in_array($displayName, ['admin', 'administrator'], true);
        });
    }

    protected function companyId(): ?int
    {
        if (! function_exists('company_id')) {
            return null;
        }

        $companyId = company_id();

        return $companyId ? (int) $companyId : null;
    }
}
