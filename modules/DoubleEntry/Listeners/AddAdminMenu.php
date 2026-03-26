<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('double-entry::general.name'), function ($sub) {
            $sub->route('double-entry.accounts.index', trans('double-entry::general.chart_of_accounts'), [], 10, ['icon' => 'account_tree']);
            $sub->url('double-entry/journal-entries', trans('double-entry::general.journal_entries'), 20, ['icon' => 'book']);
            $sub->url('double-entry/general-ledger', trans('double-entry::general.general_ledger'), 30, ['icon' => 'menu_book']);
            $sub->url('double-entry/trial-balance', trans('double-entry::general.trial_balance'), 40, ['icon' => 'balance']);
            $sub->url('double-entry/balance-sheet', trans('double-entry::general.balance_sheet'), 50, ['icon' => 'account_balance']);
            $sub->url('double-entry/profit-loss', trans('double-entry::general.profit_loss'), 60, ['icon' => 'trending_up']);
        }, 6, [
            'title' => trans('double-entry::general.name'),
            'icon' => 'balance',
        ]);
    }
}
