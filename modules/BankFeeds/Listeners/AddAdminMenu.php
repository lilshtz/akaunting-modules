<?php

namespace Modules\BankFeeds\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Add "Bank Feeds" under the Banking section
        $menu->dropdown(trans('bank-feeds::general.name'), function ($sub) {
            $sub->route('bank-feeds.transactions.index', trans('bank-feeds::general.transactions'), [], 10, [
                'icon' => 'list',
            ]);
            $sub->route('bank-feeds.matching.index', trans('bank-feeds::general.matching.name'), [], 15, [
                'icon' => 'compare_arrows',
            ]);
            $sub->route('bank-feeds.reconciliation.index', trans('bank-feeds::general.reconciliation'), [], 18, [
                'icon' => 'fact_check',
            ]);
            $sub->route('bank-feeds.imports.index', trans('bank-feeds::general.import_history'), [], 20, [
                'icon' => 'history',
            ]);
            $sub->route('bank-feeds.imports.create', trans('bank-feeds::general.import_file'), [], 30, [
                'icon' => 'cloud_upload',
            ]);
            $sub->route('bank-feeds.rules.index', trans('bank-feeds::general.rules'), [], 40, [
                'icon' => 'rule',
            ]);
            $sub->route('bank-feeds.settings.index', trans('bank-feeds::general.settings'), [], 50, [
                'icon' => 'settings',
            ]);
        }, 35, [
            'title' => trans('bank-feeds::general.name'),
            'icon' => 'account_balance',
        ]);
    }
}
