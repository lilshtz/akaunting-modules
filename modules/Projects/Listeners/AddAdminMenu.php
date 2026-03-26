<?php

namespace Modules\Projects\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $event->menu->route('projects.projects.index', trans('projects::general.name'), [], 18, [
            'icon' => 'construction',
        ]);
    }
}
