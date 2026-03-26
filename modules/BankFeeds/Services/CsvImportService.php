<?php

namespace Modules\BankFeeds\Services;

use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Models\BankFeedTransaction;

class CsvImportService
{
    /**
     * Common bank CSV column presets.
     */
    public const PRESETS = [
        'generic' => [
            'label' => 'Generic CSV',
            'date' => 0,
            'description' => 1,
            'amount' => 2,
            'type' => null,
            'credit' => null,
            'debit' => null,
        ],
        'boa' => [
            'label' => 'Bank of America',
            'date' => 0,
            'description' => 1,
            'amount' => 2,
            'type' => null,
            'credit' => null,
            'debit' => null,
        ],
        'chase' => [
            'label' => 'Chase Bank',
            'date' => 1,
            'description' => 2,
            'amount' => 3,
            'type' => 4,
            'credit' => null,
            'debit' => null,
        ],
        'chase_credit' => [
            'label' => 'Chase Credit Card',
            'date' => 0,
            'description' => 2,
            'amount' => 5,
            'type' => null,
            'credit' => null,
            'debit' => null,
        ],
    ];

    /**
     * Preview a CSV file and return the first N rows plus detected headers.
     */
    public function preview(string $filePath, int $rows = 5): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($handle);
        $preview = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < $rows) {
            $preview[] = $row;
            $count++;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $preview,
            'column_count' => count($headers ?? []),
        ];
    }

    /**
     * Import CSV rows using the provided column mapping.
     */
    public function import(BankFeedImport $import, string $filePath, array $mapping): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        // Skip header row
        fgetcsv($handle);

        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $transaction = $this->parseRow($row, $mapping);
            if (!$transaction) {
                continue;
            }

            BankFeedTransaction::create([
                'import_id' => $import->id,
                'bank_account_id' => $import->bank_account_id,
                'date' => $transaction['date'],
                'description' => $transaction['description'],
                'amount' => $transaction['amount'],
                'type' => $transaction['type'],
                'raw_data_json' => $row,
                'status' => BankFeedTransaction::STATUS_PENDING,
            ]);

            $imported++;
        }

        fclose($handle);

        return $imported;
    }

    /**
     * Parse a single CSV row using the column mapping.
     */
    protected function parseRow(array $row, array $mapping): ?array
    {
        $dateCol = $mapping['date'] ?? null;
        $descCol = $mapping['description'] ?? null;
        $amountCol = $mapping['amount'] ?? null;
        $typeCol = $mapping['type'] ?? null;
        $creditCol = $mapping['credit'] ?? null;
        $debitCol = $mapping['debit'] ?? null;

        if ($dateCol === null || $descCol === null) {
            return null;
        }

        $date = $this->parseDate($row[$dateCol] ?? '');
        if (!$date) {
            return null;
        }

        $description = trim($row[$descCol] ?? '');
        if (empty($description)) {
            return null;
        }

        // Determine amount and type
        if ($creditCol !== null && $debitCol !== null) {
            // Separate credit/debit columns
            $credit = $this->parseAmount($row[$creditCol] ?? '');
            $debit = $this->parseAmount($row[$debitCol] ?? '');

            if ($credit > 0) {
                $amount = $credit;
                $type = BankFeedTransaction::TYPE_DEPOSIT;
            } else {
                $amount = $debit > 0 ? $debit : 0;
                $type = BankFeedTransaction::TYPE_WITHDRAWAL;
            }
        } elseif ($amountCol !== null) {
            // Single amount column
            $rawAmount = $this->parseAmount($row[$amountCol] ?? '');
            $amount = abs($rawAmount);

            if ($typeCol !== null && isset($row[$typeCol])) {
                $typeValue = strtolower(trim($row[$typeCol]));
                $type = in_array($typeValue, ['credit', 'deposit', 'cr'])
                    ? BankFeedTransaction::TYPE_DEPOSIT
                    : BankFeedTransaction::TYPE_WITHDRAWAL;
            } else {
                $type = $rawAmount >= 0
                    ? BankFeedTransaction::TYPE_DEPOSIT
                    : BankFeedTransaction::TYPE_WITHDRAWAL;
            }
        } else {
            return null;
        }

        if ($amount == 0) {
            return null;
        }

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
        ];
    }

    /**
     * Parse a date string into Y-m-d format.
     */
    protected function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) {
            return null;
        }

        $formats = [
            'Y-m-d',
            'm/d/Y',
            'm/d/y',
            'd/m/Y',
            'd/m/y',
            'M d, Y',
            'M d Y',
            'Y/m/d',
            'm-d-Y',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $dateStr);
            if ($parsed && $parsed->format($format) === $dateStr) {
                return $parsed->format('Y-m-d');
            }
        }

        // Fallback to strtotime
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Parse an amount string, removing currency symbols and commas.
     */
    protected function parseAmount(string $amountStr): float
    {
        $amountStr = trim($amountStr);
        if (empty($amountStr)) {
            return 0.0;
        }

        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[^0-9.\-\(\)]/', '', $amountStr);

        // Handle parentheses as negative (accounting format)
        if (preg_match('/^\((.+)\)$/', $cleaned, $matches)) {
            return -1 * (float) $matches[1];
        }

        return (float) $cleaned;
    }
}
