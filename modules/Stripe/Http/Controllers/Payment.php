<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use App\Events\Document\PaymentReceived;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Models\Document\Document;
use Illuminate\Http\Request;
use Modules\Stripe\Models\StripePayment;
use Modules\Stripe\Models\StripeSettings;
use Modules\Stripe\Services\StripeService;

class Payment extends PaymentController
{
    public $alias = 'stripe';

    public $type = 'redirect';

    /**
     * Show the payment page / initiate Stripe Checkout.
     *
     * Creates a Stripe Checkout Session and returns a JSON response
     * with the redirect URL to Stripe's hosted payment page.
     *
     * @param  Document  $invoice
     * @param  PaymentRequest  $request
     * @param  array  $cards
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Document $invoice, PaymentRequest $request, $cards = [])
    {
        $settings = StripeSettings::where('company_id', $invoice->company_id)
            ->where('enabled', true)
            ->first();

        if (!$settings || !$settings->api_key) {
            return response()->json([
                'code' => 'stripe.card.1',
                'name' => trans('stripe::general.name'),
                'description' => '',
                'redirect' => false,
                'html' => '<div class="text-red-500">' . trans('stripe::general.payment_failed') . '</div>',
            ]);
        }

        try {
            $service = new StripeService($settings);

            $confirmUrl = $this->getConfirmUrl($invoice);
            $successUrl = $confirmUrl . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $this->getInvoiceUrl($invoice);

            $session = $service->createCheckoutSession($invoice, $successUrl, $cancelUrl);

            // Record the pending payment
            StripePayment::create([
                'company_id' => $invoice->company_id,
                'document_id' => $invoice->id,
                'stripe_session_id' => $session['id'],
                'amount' => $invoice->amount_due,
                'currency' => strtoupper($invoice->currency_code),
                'status' => 'pending',
            ]);

            $html = view('stripe::show', [
                'invoice' => $invoice,
                'checkout_url' => $session['url'],
            ])->render();

            return response()->json([
                'code' => 'stripe.card.1',
                'name' => trans('stripe::general.pay_with_card'),
                'description' => trans('stripe::general.description'),
                'redirect' => $session['url'],
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 'stripe.card.1',
                'name' => trans('stripe::general.name'),
                'description' => '',
                'redirect' => false,
                'html' => '<div class="text-red-500">' . $e->getMessage() . '</div>',
            ]);
        }
    }

    /**
     * Handle the return from Stripe Checkout.
     *
     * Verifies the checkout session and records the payment in Akaunting.
     *
     * @param  Document  $invoice
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Document $invoice, Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if (empty($sessionId)) {
                throw new \RuntimeException(trans('stripe::general.payment_failed'));
            }

            $settings = StripeSettings::where('company_id', $invoice->company_id)
                ->where('enabled', true)
                ->firstOrFail();

            $service = new StripeService($settings);
            $session = $service->retrieveCheckoutSession($sessionId);

            if ($session['payment_status'] !== 'paid') {
                throw new \RuntimeException(trans('stripe::general.payment_pending'));
            }

            // Update our stripe payment record
            $stripePayment = StripePayment::where('stripe_session_id', $sessionId)->first();

            if ($stripePayment) {
                $paymentIntent = $session['payment_intent'];
                $paymentIntentId = is_array($paymentIntent) ? $paymentIntent['id'] : $paymentIntent;
                $chargeId = null;

                if (is_array($paymentIntent) && isset($paymentIntent['latest_charge'])) {
                    $chargeId = $paymentIntent['latest_charge'];
                }

                $stripePayment->update([
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_charge_id' => $chargeId,
                    'status' => 'succeeded',
                ]);
            }

            // Build the request data for PaymentReceived event
            $request->merge([
                'payment_method' => 'stripe.card.1',
                'type' => 'income',
                'reference' => 'Stripe: ' . ($session['payment_intent']['id'] ?? $sessionId),
            ]);

            event(new PaymentReceived($invoice, $request));

            $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);

            flash($message)->success();

            return response()->json([
                'success' => true,
                'error' => false,
                'message' => $message,
                'data' => false,
                'redirect' => $this->getFinishUrl($invoice),
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            flash($message)->error()->important();

            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $message,
                'data' => false,
                'redirect' => $this->getInvoiceUrl($invoice),
            ]);
        }
    }

    /**
     * Show the payment success page.
     *
     * @param  Document  $invoice
     * @param  Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function success(Document $invoice, Request $request)
    {
        $sessionId = $request->get('session_id');
        $stripePayment = null;

        if ($sessionId) {
            $stripePayment = StripePayment::where('stripe_session_id', $sessionId)->first();
        }

        return view('stripe::success', compact('invoice', 'stripePayment'));
    }
}
