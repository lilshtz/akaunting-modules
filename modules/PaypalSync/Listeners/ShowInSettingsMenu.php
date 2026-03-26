<?php

namespace Modules\PaypalSync\Listeners;

use App\Events\Menu\SettingsCreated as Event;
use App\Traits\Modules;
use App\Traits\Permissions;

class ShowInSettingsMenu
{
    use Modules, Permissions;

    /**
     * Handle the event.
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('paypal-sync')) {
            return;
        }

        $title = trans('paypal-sync::general.name');

        if ($this->canAccessMenuItem($title, 'read-paypal-sync-settings')) {
            $event->menu->route('paypal-sync.settings.edit', $title, [], 101, [
                'icon' => 'account_balance_wallet',
                'search_keywords' => trans('paypal-sync::general.description'),
            ]);
        }
    }
}
