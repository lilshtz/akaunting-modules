<?php

namespace Modules\PaypalSync\Services;

use Illuminate\Support\Facades\Cache;
use Modules\PaypalSync\Models\PaypalSyncSettings;

class PaypalService
{
    /**
     * @var PaypalSyncSettings
     */
    protected $settings;

    /**
     * Create a new PaypalService instance.
     *
     * @param PaypalSyncSettings $settings
     */
    public function __construct(PaypalSyncSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the PayPal API base URL based on the configured mode.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->settings->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get an OAuth2 access token from PayPal.
     * Token is cached for 30 minutes per company.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'paypal_sync_token_' . $this->settings->company_id;

        return Cache::remember($cacheKey, 1800, function () {
            $response = $this->apiRequest('POST', '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ], true);

            if (empty($response['access_token'])) {
                throw new \RuntimeException('Failed to obtain PayPal access token: ' . json_encode($response));
            }

            return $response['access_token'];
        });
    }

    /**
     * Fetch transactions from PayPal Transaction Search API.
     *
     * @param string $startDate ISO 8601 datetime
     * @param string $endDate ISO 8601 datetime
     * @param int $page Page number (1-based)
     * @param int $pageSize Number of results per page
     * @return array Decoded response containing transaction_details, total_items, total_pages
     * @throws \RuntimeException
     */
    public function fetchTransactions(string $startDate, string $endDate, int $page = 1, int $pageSize = 100): array
    {
        return $this->apiRequest('GET', '/v1/reporting/transactions', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fields' => 'all',
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Make an API request to PayPal using curl.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint path
     * @param array $params Request parameters
     * @param bool $isForm Whether to send as form-encoded (for token requests)
     * @return array Decoded JSON response
     * @throws \RuntimeException
     */
    public function apiRequest(string $method, string $endpoint, array $params = [], bool $isForm = false): array
    {
        $url = $this->getBaseUrl() . $endpoint;

        $ch = curl_init();

        $headers = [
            'Accept: application/json',
        ];

        if ($isForm) {
            // Token request uses Basic auth with client credentials
            curl_setopt($ch, CURLOPT_USERPWD, $this->settings->client_id . ':' . $this->settings->client_secret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            // All other requests use Bearer token
            $token = $this->getAccessToken();
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'Content-Type: application/json';
        }

        if ($method === 'GET' && !empty($params) && !$isForm) {
            $url .= '?' . http_build_query($params);
        } elseif ($method === 'POST' && !$isForm) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('PayPal API curl error: ' . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = $decoded['message'] ?? $decoded['error_description'] ?? 'Unknown error';
            throw new \RuntimeException("PayPal API error (HTTP {$httpCode}): {$errorMessage}");
        }

        return $decoded ?? [];
    }
}
