<?php

namespace Modules\CreditDebitNotes\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        // Credit Notes under Sales (near invoices, position 28)
        $menu->route('credit-debit-notes.credit-notes.index', trans('credit-debit-notes::general.credit_notes'), [], 28, [
            'title' => trans('credit-debit-notes::general.credit_notes'),
            'icon' => 'note_add',
        ]);

        // Debit Notes under Purchases (position 45)
        $menu->route('credit-debit-notes.debit-notes.index', trans('credit-debit-notes::general.debit_notes'), [], 45, [
            'title' => trans('credit-debit-notes::general.debit_notes'),
            'icon' => 'playlist_remove',
        ]);
    }
}
