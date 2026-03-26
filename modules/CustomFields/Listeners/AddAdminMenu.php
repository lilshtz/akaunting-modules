<?php

namespace Modules\CustomFields\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Add under Settings section
        $menu->dropdown(trans('custom-fields::general.name'), function ($sub) {
            $sub->route('custom-fields.fields.index', trans('custom-fields::general.field_definitions'), [], 10, ['icon' => 'list']);
        }, 45, [
            'title' => trans('custom-fields::general.name'),
            'icon' => 'tune',
        ]);
    }
}
