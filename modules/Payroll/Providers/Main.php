<?php

namespace Modules\Payroll\Providers;

use Illuminate\Support\ServiceProvider;

class Main extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadViews();
        $this->loadMigrations();
    }

    public function register(): void
    {
        $this->loadRoutes();
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'payroll');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'payroll');
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    protected function loadRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        $path = __DIR__ . '/../Routes/admin.php';

        if (file_exists($path)) {
            $this->loadRoutesFrom($path);
        }
    }

    public function provides(): array
    {
        return [];
    }
}
