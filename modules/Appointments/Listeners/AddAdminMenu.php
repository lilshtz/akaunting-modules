<?php

namespace Modules\Appointments\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('appointments::general.name'), function ($sub) {
            $sub->route('appointments.index', trans('appointments::general.appointments'), [], 10, ['icon' => 'event']);
            $sub->route('appointments.leave.index', trans('appointments::general.leave'), [], 20, ['icon' => 'beach_access']);
            $sub->route('appointments.forms.index', trans('appointments::general.forms'), [], 30, ['icon' => 'description']);
            $sub->route('appointments.reports.index', trans('appointments::general.reports'), [], 40, ['icon' => 'insights']);
            $sub->route('appointments.settings.edit', trans('appointments::general.settings'), [], 50, ['icon' => 'settings']);
        }, 10, [
            'title' => trans('appointments::general.name'),
            'icon' => 'event',
        ]);
    }
}
