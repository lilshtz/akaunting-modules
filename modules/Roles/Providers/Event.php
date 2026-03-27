<?php

namespace Modules\Roles\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;

class Event extends Provider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\Roles\Listeners\AddAdminMenu',
        ],
        'App\Events\Module\Installed' => [
            'Modules\Roles\Listeners\FinishInstallation',
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }

    protected function discoverEventsWithin(): array
    {
        return [
            __DIR__ . '/../Listeners',
        ];
    }
}
