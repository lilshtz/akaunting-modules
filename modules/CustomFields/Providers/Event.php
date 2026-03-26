<?php

namespace Modules\CustomFields\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;

class Event extends Provider
{
    protected $listen = [
        'App\Events\Menu\AdminCreated' => [
            'Modules\CustomFields\Listeners\AddAdminMenu',
        ],
        // Document events (invoices, bills, estimates)
        'App\Events\Document\DocumentCreated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Document\DocumentUpdated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        // Contact events (customers, vendors)
        'App\Events\Common\ContactCreated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Common\ContactUpdated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        // Item events
        'App\Events\Common\ItemCreated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Common\ItemUpdated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        // Banking events
        'App\Events\Banking\TransactionCreated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Banking\TransactionUpdated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Banking\TransferCreated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
        ],
        'App\Events\Banking\TransferUpdated' => [
            'Modules\CustomFields\Listeners\SaveCustomFieldValues',
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
