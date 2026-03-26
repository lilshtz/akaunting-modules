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
            $sub->route('inventory.adjustments.index', trans('inventory::general.adjustments'), [], 30, ['icon' => 'playlist_add_check']);
            $sub->route('inventory.transfer-orders.index', trans('inventory::general.transfer_orders'), [], 40, ['icon' => 'swap_horiz']);
            $sub->route('inventory.item-groups.index', trans('inventory::general.item_groups'), [], 50, ['icon' => 'inventory']);
            $sub->route('inventory.reports.variants', trans('inventory::general.stock_by_variant_report'), [], 60, ['icon' => 'category']);
            $sub->route('inventory.reports.adjustments', trans('inventory::general.adjustment_history_report'), [], 70, ['icon' => 'report']);
            $sub->route('inventory.reports.transfers', trans('inventory::general.transfer_history_report'), [], 80, ['icon' => 'local_shipping']);
            $sub->route('inventory.reports.item-groups', trans('inventory::general.item_group_summary_report'), [], 90, ['icon' => 'view_list']);
            $sub->route('inventory.reports.status', trans('inventory::general.stock_status_report'), [], 100, ['icon' => 'assessment']);
            $sub->route('inventory.reports.value', trans('inventory::general.stock_value_report'), [], 110, ['icon' => 'payments']);
            $sub->route('inventory.history.index', trans('inventory::general.history'), [], 120, ['icon' => 'history']);
        }, 45, [
            'title' => trans('inventory::general.name'),
            'icon' => 'inventory_2',
        ]);
    }
}
