<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Menu\AdminCreated as Event;
use App\Traits\Modules;
use App\Traits\Permissions;

class AddAdminMenu
{
    use Modules, Permissions;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('double-entry')) {
            return;
        }

        $title = trans('double-entry::general.name');

        if ($this->canAccessMenuItem($title, 'read-double-entry-accounts')) {
            $event->menu->add([
                'route' => ['double-entry.accounts.index', []],
                'title' => $title,
                'icon' => 'balance',
                'order' => 15,
            ]);

            $item = $event->menu->whereTitle($title);

            $item->route('double-entry.accounts.index', trans('double-entry::general.chart_of_accounts'), [], 1, ['icon' => '']);
            $item->route('double-entry.journals.index', trans('double-entry::general.journal_entries'), [], 2, ['icon' => '']);
            $item->route('double-entry.account-defaults.index', trans('double-entry::general.account_defaults'), [], 3, ['icon' => '']);

            // Reports submenu
            $item->route('double-entry.general-ledger.index', trans('double-entry::general.general_ledger'), [], 10, ['icon' => '']);
            $item->route('double-entry.trial-balance.index', trans('double-entry::general.trial_balance'), [], 11, ['icon' => '']);
            $item->route('double-entry.balance-sheet.index', trans('double-entry::general.balance_sheet'), [], 12, ['icon' => '']);
            $item->route('double-entry.profit-loss.index', trans('double-entry::general.profit_loss'), [], 13, ['icon' => '']);
        }
    }
}
