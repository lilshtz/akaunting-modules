<?php

namespace Modules\Employees\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('employees::general.name'), function ($sub) {
            $sub->route('employees.employees.index', trans('employees::general.employees'), [], 10, ['icon' => 'badge']);
            $sub->route('employees.departments.index', trans('employees::general.departments'), [], 20, ['icon' => 'business']);
        }, 8, [
            'title' => trans('employees::general.name'),
            'icon' => 'people',
        ]);
    }
}
