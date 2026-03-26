<?php

namespace Modules\DoubleEntry\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider as Provider;
use Modules\DoubleEntry\Console\ProcessRecurringJournals;

class Main extends Provider
{
    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadViews();
        $this->loadMigrations();
        $this->loadCommands();
        $this->scheduleCommands();
    }

    public function register(): void
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

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    protected function loadRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (['admin.php', 'portal.php'] as $route) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/' . $route);
        }
    }

    protected function loadCommands(): void
    {
        $this->commands([
            ProcessRecurringJournals::class,
        ]);
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('double-entry:process-recurring')->daily();
        });
    }

    public function provides(): array
    {
        return [];
    }
}
