<?php

namespace Modules\SalesPurchaseOrders\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;

class Event extends Provider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\SalesPurchaseOrders\Listeners\AddAdminMenu',
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
