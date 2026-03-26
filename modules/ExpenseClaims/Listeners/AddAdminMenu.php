<?php

namespace Modules\ExpenseClaims\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('expense-claims::general.name'), function ($sub) {
            $sub->route('expense-claims.claims.index', trans('expense-claims::general.claims'), [], 10, [
                'icon' => 'description',
            ]);
            $sub->route('expense-claims.claims.create', trans('expense-claims::general.new_claim'), [], 20, [
                'icon' => 'add',
            ]);
            $sub->route('expense-claims.categories.index', trans('expense-claims::general.categories'), [], 30, [
                'icon' => 'category',
            ]);
            $sub->route('expense-claims.reports.index', trans('expense-claims::general.reports'), [], 40, [
                'icon' => 'insights',
            ]);
        }, 43, [
            'title' => trans('expense-claims::general.name'),
            'icon' => 'receipt_long',
        ]);
    }
}
