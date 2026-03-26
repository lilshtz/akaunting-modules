<?php

namespace Modules\Stripe\Listeners;

use App\Events\Menu\SettingsCreated as Event;
use App\Traits\Modules;
use App\Traits\Permissions;

class ShowInSettingsMenu
{
    use Modules, Permissions;

    /**
     * Handle the event.
     *
     * Adds Stripe settings to the settings menu if the module is enabled
     * and the user has permission to access it.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('stripe')) {
            return;
        }

        $title = trans('stripe::general.name');

        if ($this->canAccessMenuItem($title, 'read-stripe-settings')) {
            $event->menu->route('stripe.settings.edit', $title, [], 100, [
                'icon' => 'credit_card',
                'search_keywords' => trans('stripe::general.description'),
            ]);
        }
    }
}
