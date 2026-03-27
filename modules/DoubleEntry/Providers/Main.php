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

    public function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'double-entry');
    }

    public function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'double-entry');
    }

    public function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function loadRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        $routes = [
            'admin.php',
            'portal.php',
        ];

        foreach ($routes as $route) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/' . $route);
        }
    }

    public function provides()
    {
        return [];
    }
}
