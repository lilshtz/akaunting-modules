<?php

namespace Modules\BankFeeds\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('bank-feeds::general.name'), function ($sub): void {
            $sub->route('bank-feeds.imports.create', trans('bank-feeds::general.import_transactions'), [], 10, [
                'icon' => 'upload_file',
            ]);
            $sub->route('bank-feeds.transactions.index', trans('bank-feeds::general.transaction_review'), [], 20, [
                'icon' => 'fact_check',
            ]);
            $sub->route('bank-feeds.rules.index', trans('bank-feeds::general.categorization_rules'), [], 30, [
                'icon' => 'rule',
            ]);
            $sub->route('bank-feeds.imports.index', trans('bank-feeds::general.import_history'), [], 40, [
                'icon' => 'history',
            ]);
        }, 40, [
            'title' => trans('bank-feeds::general.name'),
            'icon' => 'account_balance',
        ]);
    }
}
