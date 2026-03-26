<?php

namespace Modules\SalesPurchaseOrders\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Sales Orders under Sales section (position 26)
        $menu->route('sales-purchase-orders.sales-orders.index', trans('sales-purchase-orders::general.sales_orders'), [], 26, [
            'title' => trans('sales-purchase-orders::general.sales_orders'),
            'icon' => 'shopping_cart',
        ]);

        // Purchase Orders under Purchases section (position 46)
        $menu->route('sales-purchase-orders.purchase-orders.index', trans('sales-purchase-orders::general.purchase_orders'), [], 46, [
            'title' => trans('sales-purchase-orders::general.purchase_orders'),
            'icon' => 'local_shipping',
        ]);
    }
}
