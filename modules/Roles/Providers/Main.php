<?php

namespace Modules\Roles\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Roles\Middleware\AuthorizeModuleAccess;
use Modules\Roles\Services\PermissionResolver;

class Main extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadViews();
        $this->loadMigrations();
        $this->registerMiddleware();
        $this->registerGateHooks();
    }

    public function register(): void
    {
        $this->loadRoutes();
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'roles');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'roles');
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

        foreach (['admin.php', 'api.php'] as $route) {
            $path = __DIR__ . '/../Routes/' . $route;

            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        $router->aliasMiddleware('roles.permission', AuthorizeModuleAccess::class);
        $router->pushMiddlewareToGroup('admin', AuthorizeModuleAccess::class);
        $router->pushMiddlewareToGroup('api', AuthorizeModuleAccess::class);
    }

    protected function registerGateHooks(): void
    {
        Gate::before(function ($user, string $ability) {
            return app(PermissionResolver::class)->resolveAbility($user, $ability);
        });
    }

    public function provides(): array
    {
        return [];
    }
}
