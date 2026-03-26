<?php

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;

class Event extends Provider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\Inventory\Listeners\AddAdminMenu',
        ],
        'App\Events\Document\DocumentCreated' => [
            'Modules\Inventory\Listeners\DocumentCreated',
        ],
        'App\Events\Document\DocumentDeleted' => [
            'Modules\Inventory\Listeners\DocumentDeleted',
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
