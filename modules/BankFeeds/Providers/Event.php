<?php

namespace Modules\BankFeeds\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class Event extends EventServiceProvider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\BankFeeds\Listeners\AddAdminMenu',
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }

    protected function discoverEventsWithin(): array
    {
        return [__DIR__ . '/../Listeners'];
    }
}
