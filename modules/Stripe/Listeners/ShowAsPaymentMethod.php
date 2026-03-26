<?php

namespace Modules\Stripe\Listeners;

use App\Events\Module\PaymentMethodShowing as Event;
use Modules\Stripe\Models\StripeSettings;

class ShowAsPaymentMethod
{
    /**
     * Handle the event.
     *
     * Adds Stripe as an available payment method if it is enabled
     * and has an API key configured for the current company.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        $settings = StripeSettings::where('company_id', company_id())
            ->where('enabled', true)
            ->first();

        if (!$settings || !$settings->api_key) {
            return;
        }

        $event->modules->payment_methods[] = [
            'code' => 'stripe.card.1',
            'name' => trans('stripe::general.pay_with_card'),
            'description' => trans('stripe::general.description'),
            'customer' => true,
            'order' => 10,
        ];
    }
}
