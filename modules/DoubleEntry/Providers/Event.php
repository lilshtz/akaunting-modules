<?php

namespace Modules\DoubleEntry\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;

class Event extends Provider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\DoubleEntry\Listeners\AddAdminMenu',
        ],
        'App\Events\Document\DocumentCreated' => [
            'Modules\DoubleEntry\Listeners\DocumentCreated',
        ],
        'App\Events\Document\DocumentUpdated' => [
            'Modules\DoubleEntry\Listeners\DocumentUpdated',
        ],
        'App\Events\Banking\TransactionCreated' => [
            'Modules\DoubleEntry\Listeners\TransactionCreated',
        ],
        'App\Events\Banking\TransferCreated' => [
            'Modules\DoubleEntry\Listeners\TransferCreated',
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
