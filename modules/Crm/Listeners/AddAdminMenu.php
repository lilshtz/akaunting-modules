<?php

namespace Modules\Crm\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('crm::general.name'), function ($sub) {
            $sub->route('crm.deals.index', trans('crm::general.deals'), [], 5, [
                'icon' => 'sell',
            ]);
            $sub->route('crm.contacts.index', trans('crm::general.contacts'), [], 10, [
                'icon' => 'person_search',
            ]);
            $sub->route('crm.companies.index', trans('crm::general.companies'), [], 20, [
                'icon' => 'domain',
            ]);
            $sub->route('crm.reports.deals', trans('crm::general.reports'), [], 30, [
                'icon' => 'analytics',
            ]);
        }, 19, [
            'title' => trans('crm::general.name'),
            'icon' => 'groups',
        ]);
    }
}
