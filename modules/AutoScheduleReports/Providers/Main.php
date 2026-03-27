<?php

namespace Modules\AutoScheduleReports\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\AutoScheduleReports\Console\RunAutoReports;

class Main extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'auto-schedule-reports');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'auto-schedule-reports');
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

        foreach (['admin.php'] as $route) {
            $path = __DIR__ . '/../Routes/' . $route;

            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function loadCommands(): void
    {
        $this->commands([
            RunAutoReports::class,
        ]);
    }

    protected function scheduleCommands(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('auto-reports:run')->hourly();
        });
    }

    public function provides(): array
    {
        return [];
    }
}
