<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;
        $title = trans('double-entry::general.name');

        if (method_exists($menu, 'add') && method_exists($menu, 'whereTitle')) {
            $menu->add([
                'route' => ['double-entry.accounts.index', []],
                'title' => $title,
                'icon' => 'balance',
                'order' => 15,
            ]);

            $item = $menu->whereTitle($title);

            if ($item) {
                $item->route('double-entry.accounts.index', trans('double-entry::general.chart_of_accounts'), [], 1, ['icon' => '']);
                $item->route('double-entry.journals.index', trans('double-entry::general.journal_entries'), [], 2, ['icon' => '']);
                $item->route('double-entry.account-defaults.index', trans('double-entry::general.account_defaults'), [], 3, ['icon' => '']);
                $item->route('double-entry.general-ledger.index', trans('double-entry::general.general_ledger'), [], 4, ['icon' => '']);
                $item->route('double-entry.trial-balance.index', trans('double-entry::general.trial_balance'), [], 5, ['icon' => '']);
                $item->route('double-entry.balance-sheet.index', trans('double-entry::general.balance_sheet'), [], 6, ['icon' => '']);
                $item->route('double-entry.profit-loss.index', trans('double-entry::general.profit_loss'), [], 7, ['icon' => '']);
            }

            return;
        }

        $menu->dropdown($title, function ($sub) {
            $sub->route('double-entry.accounts.index', trans('double-entry::general.chart_of_accounts'), [], 10, ['icon' => 'account_balance']);
            $sub->route('double-entry.journals.index', trans('double-entry::general.journal_entries'), [], 20, ['icon' => 'receipt_long']);
            $sub->route('double-entry.account-defaults.index', trans('double-entry::general.account_defaults'), [], 30, ['icon' => 'settings']);
            $sub->route('double-entry.general-ledger.index', trans('double-entry::general.general_ledger'), [], 40, ['icon' => 'menu_book']);
            $sub->route('double-entry.trial-balance.index', trans('double-entry::general.trial_balance'), [], 50, ['icon' => 'table_chart']);
            $sub->route('double-entry.balance-sheet.index', trans('double-entry::general.balance_sheet'), [], 60, ['icon' => 'balance']);
            $sub->route('double-entry.profit-loss.index', trans('double-entry::general.profit_loss'), [], 70, ['icon' => 'trending_up']);
        }, 15, [
            'title' => $title,
            'icon' => 'balance',
        ]);
    }
}
