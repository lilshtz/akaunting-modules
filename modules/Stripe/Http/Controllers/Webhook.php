<?php

namespace Modules\Stripe\Http\Controllers;

use App\Events\Document\PaymentReceived;
use App\Models\Document\Document;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Stripe\Models\StripePayment;
use Modules\Stripe\Models\StripeSettings;
use Modules\Stripe\Services\StripeService;

class Webhook extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     *
     * Validates the webhook signature and processes supported event types:
     * - checkout.session.completed
     * - payment_intent.succeeded
     * - charge.refunded
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (empty($sigHeader)) {
            return response()->json(['error' => 'Missing Stripe-Signature header'], 400);
        }

        // Try to determine company_id from the payload metadata
        $rawEvent = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Determine company_id: check metadata first, then fall back to finding settings
        $companyId = $this->extractCompanyId($rawEvent);
        $settings = null;

        if ($companyId) {
            $settings = StripeSettings::where('company_id', $companyId)
                ->where('enabled', true)
                ->first();
        }

        // If we couldn't determine company from metadata, try all enabled settings
        if (!$settings) {
            $allSettings = StripeSettings::where('enabled', true)->get();

            foreach ($allSettings as $candidateSettings) {
                try {
                    $service = new StripeService($candidateSettings);
                    $event = $service->constructWebhookEvent($payload, $sigHeader);
                    $settings = $candidateSettings;
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$settings) {
                return response()->json(['error' => 'Webhook signature verification failed'], 400);
            }
        }

        // Validate the webhook signature
        try {
            $service = new StripeService($settings);
            $event = $service->constructWebhookEvent($payload, $sigHeader);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Process the event
        $eventType = $event['type'] ?? '';

        switch ($eventType) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event, $settings);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event);
                break;
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Handle checkout.session.completed event.
     *
     * Finds or creates the StripePayment record and fires the PaymentReceived event
     * to create the transaction in Akaunting.
     *
     * @param  array  $event
     * @param  StripeSettings  $settings
     * @return void
     */
    protected function handleCheckoutSessionCompleted(array $event, StripeSettings $settings)
    {
        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        $paymentIntentId = $session['payment_intent'] ?? null;
        $metadata = $session['metadata'] ?? [];
        $documentId = $metadata['document_id'] ?? null;
        $companyId = $metadata['company_id'] ?? $settings->company_id;

        if (!$documentId) {
            return;
        }

        // Find existing stripe payment or create one
        $stripePayment = StripePayment::where('stripe_session_id', $sessionId)->first();

        if ($stripePayment && $stripePayment->status === 'succeeded') {
            // Already processed, skip to avoid duplicate payments
            return;
        }

        // Retrieve charge information
        $chargeId = null;
        if ($paymentIntentId) {
            try {
                $service = new StripeService($settings);
                $pi = $service->retrievePaymentIntent($paymentIntentId);
                $chargeId = $pi['latest_charge'] ?? null;
            } catch (\Exception $e) {
                // Non-critical, continue without charge ID
            }
        }

        $amountTotal = ($session['amount_total'] ?? 0) / 100;
        $currency = strtoupper($session['currency'] ?? 'USD');

        if ($stripePayment) {
            $stripePayment->update([
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $chargeId,
                'status' => 'succeeded',
            ]);
        } else {
            $stripePayment = StripePayment::create([
                'company_id' => $companyId,
                'document_id' => $documentId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_charge_id' => $chargeId,
                'stripe_session_id' => $sessionId,
                'amount' => $amountTotal,
                'currency' => $currency,
                'status' => 'succeeded',
            ]);
        }

        // Fire PaymentReceived event to create the transaction in Akaunting
        $document = Document::where('company_id', $companyId)->find($documentId);

        if ($document) {
            $request = request();
            $request->merge([
                'payment_method' => 'stripe.card.1',
                'type' => 'income',
                'amount' => $amountTotal,
                'currency_code' => $currency,
                'reference' => 'Stripe: ' . ($paymentIntentId ?? $sessionId),
            ]);

            try {
                event(new PaymentReceived($document, $request));
            } catch (\Exception $e) {
                // Log the error but don't fail the webhook
                \Log::error('Stripe webhook: Failed to fire PaymentReceived event', [
                    'error' => $e->getMessage(),
                    'document_id' => $documentId,
                    'session_id' => $sessionId,
                ]);
            }
        }
    }

    /**
     * Handle payment_intent.succeeded event.
     *
     * Updates the StripePayment record status to succeeded.
     *
     * @param  array  $event
     * @return void
     */
    protected function handlePaymentIntentSucceeded(array $event)
    {
        $paymentIntent = $event['data']['object'] ?? [];
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            return;
        }

        $stripePayment = StripePayment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($stripePayment) {
            $chargeId = $paymentIntent['latest_charge'] ?? $stripePayment->stripe_charge_id;

            $stripePayment->update([
                'stripe_charge_id' => $chargeId,
                'status' => 'succeeded',
            ]);
        }
    }

    /**
     * Handle charge.refunded event.
     *
     * Updates the StripePayment record status to refunded.
     *
     * @param  array  $event
     * @return void
     */
    protected function handleChargeRefunded(array $event)
    {
        $charge = $event['data']['object'] ?? [];
        $chargeId = $charge['id'] ?? null;

        if (!$chargeId) {
            return;
        }

        $stripePayment = StripePayment::where('stripe_charge_id', $chargeId)->first();

        if ($stripePayment) {
            $refundId = null;
            $refunds = $charge['refunds']['data'] ?? [];
            if (!empty($refunds)) {
                $refundId = $refunds[0]['id'] ?? null;
            }

            $stripePayment->update([
                'status' => 'refunded',
                'refund_id' => $refundId,
            ]);
        }
    }

    /**
     * Extract company_id from webhook event metadata.
     *
     * @param  array  $event
     * @return int|null
     */
    protected function extractCompanyId(array $event)
    {
        $object = $event['data']['object'] ?? [];

        // Check direct metadata
        if (isset($object['metadata']['company_id'])) {
            return (int) $object['metadata']['company_id'];
        }

        // For charge events, check if we can find by charge ID
        if (isset($object['id']) && str_starts_with($object['id'], 'ch_')) {
            $payment = StripePayment::where('stripe_charge_id', $object['id'])->first();
            if ($payment) {
                return $payment->company_id;
            }
        }

        // For payment_intent events
        if (isset($object['id']) && str_starts_with($object['id'], 'pi_')) {
            $payment = StripePayment::where('stripe_payment_intent_id', $object['id'])->first();
            if ($payment) {
                return $payment->company_id;
            }
        }

        return null;
    }
}
