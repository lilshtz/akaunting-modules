<?php

namespace Modules\Roles\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Roles\Models\Role;

class RoleTemplateService
{
    public function definitions(): array
    {
        return [
            'manager' => [
                'name' => 'module-manager',
                'display_name' => 'Manager',
                'description' => 'Full access across the company.',
            ],
            'accountant' => [
                'name' => 'module-accountant',
                'display_name' => 'Accountant',
                'description' => 'Financial access with limited administrative actions.',
            ],
            'employee' => [
                'name' => 'module-employee',
                'display_name' => 'Employee',
                'description' => 'Limited operational access.',
            ],
            'customer' => [
                'name' => 'module-customer',
                'display_name' => 'Customer',
                'description' => 'Portal-focused self-service access.',
            ],
        ];
    }

    public function ensureDefaults(int $companyId): void
    {
        foreach ($this->definitions() as $template => $definition) {
            $role = Role::firstOrCreate(
                ['name' => $definition['name']],
                Arr::only($definition, ['name', 'display_name', 'description'])
            );

            if (! $role->modulePermissions()->where('company_id', $companyId)->exists()) {
                app(PermissionResolver::class)->syncRolePermissions($role, $companyId, $this->permissionsForTemplate($template));
            }
        }
    }

    public function permissionsForTemplate(?string $template): array
    {
        $all = app(ModuleCatalog::class)->all()->pluck('alias')->all();

        return match ($template) {
            'manager' => collect($all)->mapWithKeys(fn ($alias) => [$alias => $this->fullPermission()])->all(),
            'accountant' => $this->accountantPermissions($all),
            'employee' => $this->employeePermissions($all),
            'customer' => $this->customerPermissions($all),
            default => [],
        };
    }

    protected function accountantPermissions(array $all): array
    {
        $financial = ['dashboard', 'banking', 'invoices', 'bills', 'expenses', 'reports', 'journals', 'double-entry', 'budgets'];
        $permissions = [];

        foreach ($all as $alias) {
            $permissions[$alias] = in_array($alias, $financial, true)
                ? $this->fullPermission()
                : $this->emptyPermission();
        }

        $permissions['invoices'] = ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false];

        return $permissions;
    }

    protected function employeePermissions(array $all): array
    {
        $allowed = ['dashboard', 'projects', 'receipts', 'employees', 'expense-claims', 'portal'];
        $permissions = [];

        foreach ($all as $alias) {
            $permissions[$alias] = in_array($alias, $allowed, true)
                ? ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false]
                : $this->emptyPermission();
        }

        return $permissions;
    }

    protected function customerPermissions(array $all): array
    {
        $permissions = [];

        foreach ($all as $alias) {
            $permissions[$alias] = in_array($alias, ['portal', 'invoices', 'estimates'], true)
                ? ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false]
                : $this->emptyPermission();
        }

        return $permissions;
    }

    protected function fullPermission(): array
    {
        return ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true];
    }

    protected function emptyPermission(): array
    {
        return ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false];
    }
}
