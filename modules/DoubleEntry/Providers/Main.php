<?php

namespace Modules\DoubleEntry\Providers;

use Illuminate\Support\ServiceProvider as Provider;

class Main extends Provider
{
    public function boot()
    {
        $this->loadTranslations();
        $this->loadViews();
        $this->loadMigrations();
    }

    public function register()
    {
        $this->loadRoutes();
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'double-entry');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'double-entry');
    }

    protected function loadRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (['admin.php', 'portal.php'] as $route) {
            $path = __DIR__ . '/../Routes/' . $route;

            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function provides(): array
    {
        return [];
    }
}
