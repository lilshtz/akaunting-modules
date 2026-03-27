<?php

namespace Modules\Roles\Listeners;

use App\Events\Menu\AdminCreated as Event;
use App\Traits\Permissions;

class AddAdminMenu
{
    use Permissions;

    public function handle(Event $event): void
    {
        $menu = $event->menu;
        $title = trans('roles::general.name');

        if (! $this->canAccessMenuItem($title, 'read-roles-roles')) {
            return;
        }

        $menu->dropdown($title, function ($sub) {
            $sub->route('roles.roles.index', trans('roles::general.roles'), [], 10, ['icon' => 'admin_panel_settings']);
            $sub->route('roles.assignments.index', trans('roles::general.assignments'), [], 20, ['icon' => 'group']);
        }, 11, [
            'title' => $title,
            'icon' => 'manage_accounts',
        ]);
    }
}
