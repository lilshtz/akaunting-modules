<?php

namespace Modules\PaypalSync\Listeners;

use App\Events\Auth\LandingPageShowing as Event;

class AddLandingPage
{
    /**
     * Handle the event.
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $event->user->landing_pages['paypal-sync.settings.edit'] = trans('paypal-sync::general.name');
    }
}
