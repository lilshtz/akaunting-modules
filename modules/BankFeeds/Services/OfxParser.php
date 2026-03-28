<?php

namespace Modules\BankFeeds\Services;

use Carbon\Carbon;
use RuntimeException;

class OfxParser
{
    public function parse(string $filepath): array
    {
        $contents = @file_get_contents($filepath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read OFX/QFX file.');
        }

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $contents, $matches);

        $transactions = [];

        foreach ($matches[1] ?? [] as $segment) {
            $postedAt = $this->parseDate($this->extractTag($segment, 'DTPOSTED'));
            $amount = $this->parseAmount($this->extractTag($segment, 'TRNAMT'));
            $type = $this->normalizeType($this->extractTag($segment, 'TRNTYPE'), $amount);
            $description = $this->buildDescription(
                $this->extractTag($segment, 'NAME'),
                $this->extractTag($segment, 'MEMO')
            );

            if (! $postedAt || $amount === null || ! $type || $description === '') {
                continue;
            }

            $transactions[] = [
                'date' => $postedAt,
                'description' => $description,
                'amount' => number_format(abs($amount), 4, '.', ''),
                'type' => $type,
                'raw_data_json' => [
                    'trntype' => $this->extractTag($segment, 'TRNTYPE'),
                    'dtposted' => $this->extractTag($segment, 'DTPOSTED'),
                    'trnamt' => $this->extractTag($segment, 'TRNAMT'),
                    'name' => $this->extractTag($segment, 'NAME'),
                    'memo' => $this->extractTag($segment, 'MEMO'),
                ],
            ];
        }

        return $transactions;
    }

    protected function extractTag(string $segment, string $tag): ?string
    {
        if (! preg_match('/<' . preg_quote($tag, '/') . '>([^<\r\n]+)/i', $segment, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    protected function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([',', '$', ' '], '', $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    protected function normalizeType(?string $value, float $amount): ?string
    {
        $type = strtoupper(trim((string) $value));

        return match ($type) {
            'DEBIT', 'POS' => 'withdrawal',
            'CREDIT' => 'deposit',
            'XFER' => $amount < 0 ? 'withdrawal' : 'deposit',
            default => $amount < 0 ? 'withdrawal' : 'deposit',
        };
    }

    protected function buildDescription(?string $name, ?string $memo): string
    {
        $name = trim((string) $name);
        $memo = trim((string) $memo);

        if ($name === '') {
            return $memo;
        }

        if ($memo === '' || strcasecmp($name, $memo) === 0) {
            return $name;
        }

        return trim($name . ' - ' . $memo);
    }

    protected function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = substr(preg_replace('/[^0-9]/', '', $value), 0, 8);

        if (strlen($normalized) !== 8) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd', $normalized)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
