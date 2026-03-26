<?php

namespace Modules\Stripe\Listeners;

use App\Events\Auth\LandingPageShowing as Event;

class AddLandingPage
{
    /**
     * Handle the event.
     *
     * Adds the Stripe settings page as an available landing page option.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        $event->user->landing_pages['stripe.settings.edit'] = trans('stripe::general.name');
    }
}
