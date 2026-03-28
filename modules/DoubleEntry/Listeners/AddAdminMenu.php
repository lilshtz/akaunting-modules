<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('double-entry::general.name'), function ($sub) {
            $sub->route('double-entry.accounts.index', trans('double-entry::general.chart_of_accounts'), [], 10, [
                'icon' => 'account_tree',
            ]);
            $sub->route('double-entry.journals.index', trans('double-entry::general.journal_entries'), [], 20, [
                'icon' => 'receipt_long',
            ]);
            $sub->route('double-entry.general-ledger.index', trans('double-entry::general.general_ledger'), [], 30, [
                'icon' => 'menu_book',
            ]);
            $sub->route('double-entry.trial-balance.index', trans('double-entry::general.trial_balance'), [], 40, [
                'icon' => 'balance',
            ]);
            $sub->route('double-entry.balance-sheet.index', trans('double-entry::general.balance_sheet'), [], 50, [
                'icon' => 'stacked_bar_chart',
            ]);
            $sub->route('double-entry.profit-loss.index', trans('double-entry::general.profit_loss'), [], 60, [
                'icon' => 'query_stats',
            ]);
            $sub->route('double-entry.account-defaults.index', trans('double-entry::general.account_defaults'), [], 70, [
                'icon' => 'tune',
            ]);
        }, 35, [
            'title' => trans('double-entry::general.name'),
            'icon' => 'balance',
        ]);
    }
}
