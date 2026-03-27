<?php

namespace Modules\Pos\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('pos::general.name'), function ($sub) {
            $sub->route('pos.orders.index', trans('pos::general.pos_terminal'), [], 10, [
                'icon' => 'point_of_sale',
            ]);
            $sub->route('pos.orders.history', trans('pos::general.order_history'), [], 20, [
                'icon' => 'history',
            ]);
            $sub->route('pos.reports.daily', trans('pos::general.daily_sales_summary'), [], 30, [
                'icon' => 'assessment',
            ]);
            $sub->route('pos.settings.edit', trans('pos::general.settings'), [], 40, [
                'icon' => 'settings',
            ]);
        }, 46, [
            'title' => trans('pos::general.name'),
            'icon' => 'point_of_sale',
        ]);
    }
}
