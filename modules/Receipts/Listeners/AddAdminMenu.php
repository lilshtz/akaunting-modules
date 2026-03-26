<?php

namespace Modules\Receipts\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Add "Receipts" under the Purchases section
        // Find the purchases menu and add receipt as a child, or add as standalone
        $menu->dropdown(trans('receipts::general.name'), function ($sub) {
            $sub->route('receipts.receipts.index', trans('receipts::general.receipt_inbox'), [], 10, [
                'icon' => 'inbox',
            ]);
            $sub->route('receipts.receipts.upload', trans('receipts::general.upload_receipt'), [], 20, [
                'icon' => 'cloud_upload',
            ]);
            $sub->route('receipts.settings.index', trans('receipts::general.settings'), [], 30, [
                'icon' => 'settings',
            ]);
        }, 42, [
            'title' => trans('receipts::general.name'),
            'icon' => 'receipt_long',
        ]);
    }
}
