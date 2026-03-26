<?php

namespace Modules\Estimates\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Add under Sales section (position 3 to be near invoices)
        $menu->route('estimates.estimates.index', trans('estimates::general.estimates'), [], 25, [
            'title' => trans('estimates::general.estimates'),
            'icon' => 'request_quote',
        ]);
    }
}
