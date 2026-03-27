<?php

namespace Modules\Roles\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModuleCatalog
{
    public function all(): Collection
    {
        $modulesPath = base_path('modules');
        $modules = collect();

        foreach (glob($modulesPath . '/*/module.json') ?: [] as $file) {
            $directory = basename(dirname($file));

            if (Str::startsWith($directory, '_')) {
                continue;
            }

            $json = json_decode((string) file_get_contents($file), true) ?: [];
            $alias = $json['alias'] ?? Str::kebab($directory);

            $modules->put($alias, [
                'alias' => $alias,
                'title' => $this->titleForAlias($alias, $directory),
                'source' => 'module',
            ]);
        }

        foreach ($this->coreModules() as $alias => $title) {
            $modules->put($alias, [
                'alias' => $alias,
                'title' => $title,
                'source' => 'core',
            ]);
        }

        return $modules->sortBy('title')->values();
    }

    protected function coreModules(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'banking' => 'Banking',
            'customers' => 'Customers',
            'vendors' => 'Vendors',
            'invoices' => 'Invoices',
            'bills' => 'Bills',
            'expenses' => 'Expenses',
            'sales' => 'Sales',
            'purchases' => 'Purchases',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'journals' => 'Journals',
            'portal' => 'Portal',
            'api' => 'API',
        ];
    }

    protected function titleForAlias(string $alias, string $directory): string
    {
        $translationKey = $alias . '::general.name';
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return Str::of($directory)
            ->replace('-', ' ')
            ->headline()
            ->value();
    }
}
