<?php

namespace Modules\Receipts\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrService
{
    protected string $provider;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->provider = setting('receipts.ocr_provider', 'tesseract');
        $this->apiKey = setting('receipts.ocr_api_key');
    }

    /**
     * Extract data from a receipt image.
     *
     * @return array{vendor_name: ?string, date: ?string, amount: ?float, tax_amount: ?float, currency: ?string, raw: array}
     */
    public function extract(string $imagePath): array
    {
        try {
            return match ($this->provider) {
                'tesseract' => $this->extractWithTesseract($imagePath),
                'taggun' => $this->extractWithTaggun($imagePath),
                'mindee' => $this->extractWithMindee($imagePath),
                default => $this->extractWithTesseract($imagePath),
            };
        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'provider' => $this->provider,
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'vendor_name' => null,
                'date' => null,
                'amount' => null,
                'tax_amount' => null,
                'currency' => null,
                'raw' => ['error' => $e->getMessage()],
            ];
        }
    }

    protected function extractWithTesseract(string $imagePath): array
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'ocr_');

        $command = sprintf(
            'tesseract %s %s -l eng 2>&1',
            escapeshellarg($imagePath),
            escapeshellarg($outputFile)
        );

        exec($command, $output, $returnCode);

        $text = '';
        if (file_exists($outputFile . '.txt')) {
            $text = file_get_contents($outputFile . '.txt');
            @unlink($outputFile . '.txt');
        }
        @unlink($outputFile);

        if (empty($text)) {
            return [
                'vendor_name' => null,
                'date' => null,
                'amount' => null,
                'tax_amount' => null,
                'currency' => null,
                'raw' => ['text' => '', 'return_code' => $returnCode],
            ];
        }

        return $this->parseReceiptText($text);
    }

    protected function extractWithTaggun(string $imagePath): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Taggun API key not configured');
        }

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->attach(
            'file',
            file_get_contents($imagePath),
            basename($imagePath)
        )->post('https://api.taggun.io/api/receipt/v1/verbose/file');

        if (!$response->successful()) {
            throw new \RuntimeException('Taggun API request failed: ' . $response->status());
        }

        $data = $response->json();

        return [
            'vendor_name' => $data['merchantName']['data'] ?? null,
            'date' => isset($data['date']['data']) ? date('Y-m-d', strtotime($data['date']['data'])) : null,
            'amount' => $data['totalAmount']['data'] ?? null,
            'tax_amount' => $data['taxAmount']['data'] ?? null,
            'currency' => $data['totalAmount']['currencyCode'] ?? null,
            'raw' => $data,
        ];
    }

    protected function extractWithMindee(string $imagePath): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Mindee API key not configured');
        }

        $response = Http::withToken($this->apiKey)
            ->attach(
                'document',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post('https://api.mindee.net/v1/products/mindee/expense_receipts/v5/predict');

        if (!$response->successful()) {
            throw new \RuntimeException('Mindee API request failed: ' . $response->status());
        }

        $data = $response->json();
        $prediction = $data['document']['inference']['prediction'] ?? [];

        return [
            'vendor_name' => $prediction['supplier_name']['value'] ?? null,
            'date' => $prediction['date']['value'] ?? null,
            'amount' => $prediction['total_amount']['value'] ?? null,
            'tax_amount' => $prediction['total_tax']['value'] ?? null,
            'currency' => $prediction['locale']['currency'] ?? null,
            'raw' => $data,
        ];
    }

    /**
     * Parse raw OCR text to extract receipt fields.
     */
    protected function parseReceiptText(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        $vendorName = $this->extractVendorName($lines);
        $date = $this->extractDate($text);
        $amounts = $this->extractAmounts($text);
        $currency = $this->extractCurrency($text);

        return [
            'vendor_name' => $vendorName,
            'date' => $date,
            'amount' => $amounts['total'],
            'tax_amount' => $amounts['tax'],
            'currency' => $currency,
            'raw' => ['text' => $text, 'lines' => $lines],
        ];
    }

    protected function extractVendorName(array $lines): ?string
    {
        // First non-empty line is typically the vendor/store name
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) >= 3 && !preg_match('/^\d/', $line)) {
                return $line;
            }
        }

        return null;
    }

    protected function extractDate(string $text): ?string
    {
        $patterns = [
            '/(\d{4}[-\/]\d{2}[-\/]\d{2})/',             // 2024-01-15
            '/(\d{2}[-\/]\d{2}[-\/]\d{4})/',             // 01/15/2024
            '/(\d{2}[-\/]\d{2}[-\/]\d{2})\b/',           // 01/15/24
            '/(\w{3,9}\s+\d{1,2},?\s+\d{4})/',           // January 15, 2024
            '/(\d{1,2}\s+\w{3,9}\s+\d{4})/',             // 15 January 2024
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $timestamp = strtotime($matches[1]);
                if ($timestamp !== false) {
                    return date('Y-m-d', $timestamp);
                }
            }
        }

        return null;
    }

    protected function extractAmounts(string $text): array
    {
        $total = null;
        $tax = null;

        // Look for total amount (usually the largest or last amount labeled "total")
        if (preg_match('/(?:total|amount\s*due|grand\s*total|balance\s*due)[:\s]*\$?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $total = (float) str_replace(',', '', $matches[1]);
        }

        // Look for tax amount
        if (preg_match('/(?:tax|hst|gst|vat|sales\s*tax)[:\s]*\$?\s*([\d,]+\.?\d*)/i', $text, $matches)) {
            $tax = (float) str_replace(',', '', $matches[1]);
        }

        // If no labeled total, find the largest dollar amount
        if ($total === null) {
            preg_match_all('/\$\s*([\d,]+\.\d{2})/', $text, $matches);
            if (!empty($matches[1])) {
                $amounts = array_map(function ($v) {
                    return (float) str_replace(',', '', $v);
                }, $matches[1]);
                $total = max($amounts);
            }
        }

        return ['total' => $total, 'tax' => $tax];
    }

    protected function extractCurrency(string $text): ?string
    {
        $currencyMap = [
            '$' => 'USD',
            '€' => 'EUR',
            '£' => 'GBP',
            '¥' => 'JPY',
            'CAD' => 'CAD',
            'AUD' => 'AUD',
            'USD' => 'USD',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
        ];

        foreach ($currencyMap as $symbol => $code) {
            if (str_contains($text, $symbol)) {
                return $code;
            }
        }

        return null;
    }
}
