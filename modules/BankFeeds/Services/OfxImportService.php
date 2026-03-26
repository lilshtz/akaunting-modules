<?php

namespace Modules\BankFeeds\Services;

use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Models\BankFeedTransaction;

class OfxImportService
{
    /**
     * Import transactions from an OFX/QFX file.
     */
    public function import(BankFeedImport $import, string $filePath): int
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Unable to read OFX file.');
        }

        $transactions = $this->parseOfx($content);
        $imported = 0;

        foreach ($transactions as $txn) {
            BankFeedTransaction::create([
                'import_id' => $import->id,
                'bank_account_id' => $import->bank_account_id,
                'date' => $txn['date'],
                'description' => $txn['description'],
                'amount' => $txn['amount'],
                'type' => $txn['type'],
                'raw_data_json' => $txn['raw'],
                'status' => BankFeedTransaction::STATUS_PENDING,
            ]);

            $imported++;
        }

        return $imported;
    }

    /**
     * Parse OFX/QFX content into an array of transactions.
     */
    protected function parseOfx(string $content): array
    {
        $transactions = [];

        // Extract all STMTTRN blocks
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $content, $matches);

        if (empty($matches[1])) {
            // Try SGML-style OFX (no closing tags)
            return $this->parseSgmlOfx($content);
        }

        foreach ($matches[1] as $block) {
            $txn = $this->parseTransactionBlock($block);
            if ($txn) {
                $transactions[] = $txn;
            }
        }

        return $transactions;
    }

    /**
     * Parse SGML-style OFX (older format without closing tags).
     */
    protected function parseSgmlOfx(string $content): array
    {
        $transactions = [];

        // Split on STMTTRN markers
        $parts = preg_split('/<STMTTRN>\s*/i', $content);
        array_shift($parts); // Remove everything before first STMTTRN

        foreach ($parts as $block) {
            // Take content up to next tag boundary or end
            $endPos = stripos($block, '<STMTTRN>');
            if ($endPos === false) {
                $endPos = stripos($block, '</BANKTRANLIST>');
            }
            if ($endPos === false) {
                $endPos = stripos($block, '</STMTTRNRS>');
            }
            if ($endPos !== false) {
                $block = substr($block, 0, $endPos);
            }

            $txn = $this->parseSgmlTransactionBlock($block);
            if ($txn) {
                $transactions[] = $txn;
            }
        }

        return $transactions;
    }

    /**
     * Parse a single XML-style STMTTRN block.
     */
    protected function parseTransactionBlock(string $block): ?array
    {
        $type = $this->extractTag($block, 'TRNTYPE');
        $datePosted = $this->extractTag($block, 'DTPOSTED');
        $amount = $this->extractTag($block, 'TRNAMT');
        $name = $this->extractTag($block, 'NAME');
        $memo = $this->extractTag($block, 'MEMO');
        $fitid = $this->extractTag($block, 'FITID');

        if (!$datePosted || $amount === null) {
            return null;
        }

        $parsedAmount = (float) $amount;
        $description = trim(($name ?: '') . ($memo ? ' ' . $memo : ''));

        return [
            'date' => $this->parseOfxDate($datePosted),
            'description' => $description ?: 'Unknown',
            'amount' => abs($parsedAmount),
            'type' => $parsedAmount >= 0
                ? BankFeedTransaction::TYPE_DEPOSIT
                : BankFeedTransaction::TYPE_WITHDRAWAL,
            'raw' => [
                'trntype' => $type,
                'dtposted' => $datePosted,
                'trnamt' => $amount,
                'name' => $name,
                'memo' => $memo,
                'fitid' => $fitid,
            ],
        ];
    }

    /**
     * Parse a SGML-style transaction block (no closing tags).
     */
    protected function parseSgmlTransactionBlock(string $block): ?array
    {
        $type = $this->extractSgmlTag($block, 'TRNTYPE');
        $datePosted = $this->extractSgmlTag($block, 'DTPOSTED');
        $amount = $this->extractSgmlTag($block, 'TRNAMT');
        $name = $this->extractSgmlTag($block, 'NAME');
        $memo = $this->extractSgmlTag($block, 'MEMO');
        $fitid = $this->extractSgmlTag($block, 'FITID');

        if (!$datePosted || $amount === null) {
            return null;
        }

        $parsedAmount = (float) $amount;
        $description = trim(($name ?: '') . ($memo ? ' ' . $memo : ''));

        return [
            'date' => $this->parseOfxDate($datePosted),
            'description' => $description ?: 'Unknown',
            'amount' => abs($parsedAmount),
            'type' => $parsedAmount >= 0
                ? BankFeedTransaction::TYPE_DEPOSIT
                : BankFeedTransaction::TYPE_WITHDRAWAL,
            'raw' => [
                'trntype' => $type,
                'dtposted' => $datePosted,
                'trnamt' => $amount,
                'name' => $name,
                'memo' => $memo,
                'fitid' => $fitid,
            ],
        ];
    }

    /**
     * Extract a tag value from XML-style content.
     */
    protected function extractTag(string $block, string $tag): ?string
    {
        if (preg_match('/<' . $tag . '>(.*?)<\/' . $tag . '>/si', $block, $match)) {
            return trim($match[1]);
        }
        // Fallback to SGML style
        return $this->extractSgmlTag($block, $tag);
    }

    /**
     * Extract a tag value from SGML-style content (no closing tags).
     */
    protected function extractSgmlTag(string $block, string $tag): ?string
    {
        if (preg_match('/<' . $tag . '>\s*([^\r\n<]+)/i', $block, $match)) {
            return trim($match[1]);
        }
        return null;
    }

    /**
     * Parse OFX date format (YYYYMMDDHHMMSS or YYYYMMDD).
     */
    protected function parseOfxDate(string $dateStr): string
    {
        // Remove timezone info in brackets
        $dateStr = preg_replace('/\[.*\]/', '', $dateStr);
        $dateStr = trim($dateStr);

        // Take first 8 characters (YYYYMMDD)
        $dateOnly = substr($dateStr, 0, 8);

        $parsed = \DateTime::createFromFormat('Ymd', $dateOnly);
        if ($parsed) {
            return $parsed->format('Y-m-d');
        }

        return date('Y-m-d');
    }
}
