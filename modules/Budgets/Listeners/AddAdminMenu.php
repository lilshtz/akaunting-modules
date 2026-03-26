<?php

namespace Modules\Budgets\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('budgets::general.name'), function ($sub) {
            $sub->route('budgets.budgets.index', trans('budgets::general.budgets'), [], 10, [
                'icon' => 'account_balance_wallet',
            ]);
            $sub->route('budgets.budgets.create', trans('budgets::general.new_budget'), [], 20, [
                'icon' => 'add',
            ]);
        }, 61, [
            'title' => trans('budgets::general.name'),
            'icon' => 'account_balance_wallet',
        ]);
    }
}
