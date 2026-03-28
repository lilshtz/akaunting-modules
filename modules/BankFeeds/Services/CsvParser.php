<?php

namespace Modules\BankFeeds\Services;

use Carbon\Carbon;
use RuntimeException;

class CsvParser
{
    public function parseHeaders(string $filepath): array
    {
        $handle = fopen($filepath, 'r');

        if (! $handle) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($handle) ?: [];
        fclose($handle);

        return array_map(fn ($header) => trim((string) $header), $headers);
    }

    public function parseRows(string $filepath, array $mapping): array
    {
        $handle = fopen($filepath, 'r');

        if (! $handle) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $date = $this->parseDate($this->mappedValue($row, $mapping['date'] ?? null));
            $description = trim((string) $this->mappedValue($row, $mapping['description'] ?? null));
            $type = $this->normalizeType($this->mappedValue($row, $mapping['type'] ?? null));
            [$amount, $derivedType] = $this->parseAmountSet(
                $this->mappedValue($row, $mapping['amount'] ?? null),
                $this->mappedValue($row, $mapping['debit'] ?? null),
                $this->mappedValue($row, $mapping['credit'] ?? null)
            );

            $type = $type ?: $derivedType;

            if (! $date || $description === '' || $amount === null || ! $type) {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'description' => $description,
                'amount' => number_format($amount, 4, '.', ''),
                'type' => $type,
                'raw_data_json' => $this->combineRow($headers, $row),
            ];
        }

        fclose($handle);

        return $rows;
    }

    protected function mappedValue(array $row, $index): ?string
    {
        if ($index === null || $index === '') {
            return null;
        }

        return array_key_exists((int) $index, $row)
            ? trim((string) $row[(int) $index])
            : null;
    }

    protected function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        foreach (['m/d/Y', 'Y-m-d', 'm-d-Y', 'n/j/Y', 'n-j-Y', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseAmountSet(?string $amountValue, ?string $debitValue, ?string $creditValue): array
    {
        $credit = $this->sanitizeAmount($creditValue);
        $debit = $this->sanitizeAmount($debitValue);

        if ($credit !== null || $debit !== null) {
            if ($credit !== null && $credit > 0) {
                return [$credit, 'deposit'];
            }

            if ($debit !== null && $debit > 0) {
                return [$debit, 'withdrawal'];
            }

            return [null, null];
        }

        $amount = $this->sanitizeAmount($amountValue);

        if ($amount === null || $amount == 0.0) {
            return [null, null];
        }

        return [abs($amount), $amount < 0 ? 'withdrawal' : 'deposit'];
    }

    protected function sanitizeAmount(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(['$', ',', ' '], '', $normalized);

        if (preg_match('/^\((.*)\)$/', $normalized, $matches)) {
            $normalized = '-' . $matches[1];
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    protected function normalizeType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match (true) {
            in_array($normalized, ['deposit', 'credit', 'inflow', 'incoming'], true) => 'deposit',
            in_array($normalized, ['withdrawal', 'debit', 'outflow', 'payment'], true) => 'withdrawal',
            default => null,
        };
    }

    protected function combineRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $combined[$header !== '' ? $header : 'column_' . $index] = $row[$index] ?? null;
        }

        return $combined;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
