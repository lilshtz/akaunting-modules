<?php

namespace Modules\Stripe\Services;

use Modules\Stripe\Models\StripeSettings;

class StripeService
{
    /**
     * The Stripe settings instance.
     *
     * @var StripeSettings
     */
    protected $settings;

    /**
     * Stripe API base URL.
     *
     * @var string
     */
    protected $baseUrl = 'https://api.stripe.com/v1';

    /**
     * Create a new StripeService instance.
     *
     * @param  StripeSettings  $settings
     */
    public function __construct(StripeSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the API key (respects test_mode setting).
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->settings->api_key;
    }

    /**
     * Create a Stripe Checkout Session.
     *
     * @param  \App\Models\Document\Document  $invoice
     * @param  string  $successUrl
     * @param  string  $cancelUrl
     * @return array
     *
     * @throws \RuntimeException
     */
    public function createCheckoutSession($invoice, $successUrl, $cancelUrl)
    {
        $currency = strtolower($invoice->currency_code);
        $amount = $this->convertToSmallestUnit($invoice->amount_due, $currency);

        $params = [
            'payment_method_types[]' => 'card',
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => trans('documents.invoice_number', ['number' => $invoice->document_number]),
            'line_items[0][price_data][product_data][description]' => trans('stripe::general.description'),
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][quantity]' => 1,
            'metadata[document_id]' => $invoice->id,
            'metadata[company_id]' => $invoice->company_id,
        ];

        // Add customer email if available
        if ($invoice->contact && $invoice->contact->email) {
            $params['customer_email'] = $invoice->contact->email;
        }

        return $this->apiRequest('POST', '/checkout/sessions', $params);
    }

    /**
     * Retrieve a Payment Intent from Stripe.
     *
     * @param  string  $paymentIntentId
     * @return array
     *
     * @throws \RuntimeException
     */
    public function retrievePaymentIntent($paymentIntentId)
    {
        return $this->apiRequest('GET', '/payment_intents/' . $paymentIntentId);
    }

    /**
     * Retrieve a Checkout Session from Stripe.
     *
     * @param  string  $sessionId
     * @return array
     *
     * @throws \RuntimeException
     */
    public function retrieveCheckoutSession($sessionId)
    {
        return $this->apiRequest('GET', '/checkout/sessions/' . $sessionId, [
            'expand[]' => 'payment_intent',
        ]);
    }

    /**
     * Create a refund via Stripe API.
     *
     * @param  string  $chargeId
     * @param  int|null  $amount  Amount in smallest currency unit (e.g., cents). Null for full refund.
     * @return array
     *
     * @throws \RuntimeException
     */
    public function createRefund($chargeId, $amount = null)
    {
        $params = [
            'charge' => $chargeId,
        ];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        return $this->apiRequest('POST', '/refunds', $params);
    }

    /**
     * Validate and construct a webhook event from the incoming payload.
     *
     * Validates the Stripe-Signature header using HMAC-SHA256 per Stripe's
     * webhook signing specification.
     *
     * @param  string  $payload  Raw request body
     * @param  string  $sigHeader  Value of the Stripe-Signature header
     * @return array  Decoded event payload
     *
     * @throws \RuntimeException  If signature validation fails
     */
    public function constructWebhookEvent($payload, $sigHeader)
    {
        $webhookSecret = $this->settings->webhook_secret;

        if (empty($webhookSecret)) {
            throw new \RuntimeException('Webhook secret is not configured.');
        }

        // Parse the signature header
        $sigParts = [];
        $pairs = explode(',', $sigHeader);
        foreach ($pairs as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) {
                $sigParts[trim($kv[0])] = trim($kv[1]);
            }
        }

        if (!isset($sigParts['t']) || !isset($sigParts['v1'])) {
            throw new \RuntimeException('Invalid Stripe webhook signature format.');
        }

        $timestamp = $sigParts['t'];
        $expectedSignature = $sigParts['v1'];

        // Check timestamp tolerance (5 minutes)
        $tolerance = 300;
        $currentTime = time();
        if (abs($currentTime - (int)$timestamp) > $tolerance) {
            throw new \RuntimeException('Webhook timestamp is outside the tolerance zone.');
        }

        // Compute expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        // Constant-time comparison
        if (!hash_equals($computedSignature, $expectedSignature)) {
            throw new \RuntimeException('Webhook signature verification failed.');
        }

        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON payload in webhook.');
        }

        return $event;
    }

    /**
     * Make a low-level API request to Stripe using curl.
     *
     * @param  string  $method  HTTP method (GET, POST, DELETE)
     * @param  string  $endpoint  API endpoint path (e.g., /checkout/sessions)
     * @param  array  $params  Request parameters
     * @return array  Decoded JSON response
     *
     * @throws \RuntimeException  If the API request fails
     */
    public function apiRequest($method, $endpoint, $params = [])
    {
        $apiKey = $this->getApiKey();

        if (empty($apiKey)) {
            throw new \RuntimeException('Stripe API key is not configured.');
        }

        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $method = strtoupper($method);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_URL, $url);
        } elseif ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('Stripe API request failed: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = 'Stripe API error';
            if (isset($decoded['error']['message'])) {
                $errorMessage = $decoded['error']['message'];
            }
            throw new \RuntimeException($errorMessage);
        }

        return $decoded;
    }

    /**
     * Convert an amount to the smallest currency unit (e.g., dollars to cents).
     *
     * Some currencies like JPY are already in the smallest unit (zero-decimal currencies).
     *
     * @param  float  $amount
     * @param  string  $currency  ISO 4217 currency code (lowercase)
     * @return int
     */
    protected function convertToSmallestUnit($amount, $currency)
    {
        // Zero-decimal currencies per Stripe documentation
        $zeroDecimalCurrencies = [
            'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
            'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
        ];

        if (in_array(strtolower($currency), $zeroDecimalCurrencies)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }
}
