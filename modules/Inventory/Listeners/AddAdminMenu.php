<?php

namespace Modules\Inventory\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('inventory::general.name'), function ($sub) {
            $sub->route('inventory.warehouses.index', trans('inventory::general.warehouses'), [], 10, ['icon' => 'warehouse']);
            $sub->route('inventory.stock.index', trans('inventory::general.stock'), [], 20, ['icon' => 'inventory']);
            $sub->route('inventory.reports.status', trans('inventory::general.stock_status_report'), [], 30, ['icon' => 'assessment']);
            $sub->route('inventory.reports.value', trans('inventory::general.stock_value_report'), [], 40, ['icon' => 'payments']);
            $sub->route('inventory.history.index', trans('inventory::general.history'), [], 50, ['icon' => 'history']);
        }, 45, [
            'title' => trans('inventory::general.name'),
            'icon' => 'inventory_2',
        ]);
    }
}
