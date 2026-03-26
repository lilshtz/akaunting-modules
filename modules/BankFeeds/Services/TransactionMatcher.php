<?php

namespace Modules\BankFeeds\Services;

use App\Models\Banking\Transaction;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Illuminate\Support\Collection;

class TransactionMatcher
{
    protected int $dateRangeDays = 3;
    protected float $autoMatchThreshold = 85.0;

    public function __construct()
    {
        $this->autoMatchThreshold = (float) setting('bank_feeds.auto_match_threshold', 85.0);
        $this->dateRangeDays = (int) setting('bank_feeds.date_range_days', 3);
    }

    /**
     * Find matching Akaunting transactions for a bank feed transaction.
     * Returns top 3 matches with confidence scores.
     */
    public function findMatches(BankFeedTransaction $bankTxn): array
    {
        $candidates = $this->getCandidates($bankTxn);
        $scored = [];

        foreach ($candidates as $candidate) {
            $score = $this->calculateConfidence($bankTxn, $candidate);
            if ($score > 0) {
                $scored[] = [
                    'transaction' => $candidate,
                    'confidence' => round($score, 2),
                    'reasons' => $this->getMatchReasons($bankTxn, $candidate),
                ];
            }
        }

        // Sort by confidence descending
        usort($scored, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($scored, 0, 3);
    }

    /**
     * Auto-match all unmatched transactions for a company.
     * Returns count of auto-matched transactions.
     */
    public function autoMatch(int $companyId): int
    {
        $unmatched = BankFeedTransaction::whereHas('import', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->unmatched()->get();

        $matched = 0;

        foreach ($unmatched as $bankTxn) {
            $matches = $this->findMatches($bankTxn);

            if (!empty($matches) && $matches[0]['confidence'] >= $this->autoMatchThreshold) {
                $this->applyMatch($bankTxn, $matches[0]['transaction']->id, $matches[0]['confidence']);
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Apply a match between a bank feed transaction and an Akaunting transaction.
     */
    public function applyMatch(BankFeedTransaction $bankTxn, int $transactionId, ?float $confidence = null): void
    {
        $bankTxn->update([
            'matched_transaction_id' => $transactionId,
            'match_confidence' => $confidence,
            'status' => BankFeedTransaction::STATUS_MATCHED,
        ]);
    }

    /**
     * Remove a match from a bank feed transaction.
     */
    public function unmatch(BankFeedTransaction $bankTxn): void
    {
        $bankTxn->update([
            'matched_transaction_id' => null,
            'match_confidence' => null,
            'status' => BankFeedTransaction::STATUS_PENDING,
        ]);
    }

    /**
     * Get candidate Akaunting transactions that could match.
     */
    protected function getCandidates(BankFeedTransaction $bankTxn): Collection
    {
        $startDate = $bankTxn->date->copy()->subDays($this->dateRangeDays);
        $endDate = $bankTxn->date->copy()->addDays($this->dateRangeDays);

        // Determine which transaction types to look for
        $types = $bankTxn->type === BankFeedTransaction::TYPE_DEPOSIT
            ? ['income', 'income-transfer']
            : ['expense', 'expense-transfer'];

        // Get already matched transaction IDs to exclude
        $alreadyMatched = BankFeedTransaction::whereNotNull('matched_transaction_id')
            ->where('id', '!=', $bankTxn->id)
            ->pluck('matched_transaction_id')
            ->toArray();

        return Transaction::where('account_id', $bankTxn->bank_account_id)
            ->whereIn('type', $types)
            ->whereBetween('paid_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNotIn('id', $alreadyMatched)
            ->whereNull('deleted_at')
            ->with(['contact', 'category', 'document'])
            ->get();
    }

    /**
     * Calculate confidence score (0-100) for a potential match.
     */
    protected function calculateConfidence(BankFeedTransaction $bankTxn, Transaction $candidate): float
    {
        $score = 0.0;

        // Amount match (max 50 points)
        $score += $this->scoreAmount($bankTxn->amount, $candidate->amount);

        // Date match (max 30 points)
        $score += $this->scoreDate($bankTxn->date, $candidate->paid_at);

        // Vendor/description match (max 20 points)
        $score += $this->scoreVendor($bankTxn, $candidate);

        return min($score, 100.0);
    }

    /**
     * Score amount similarity. Exact match = 50, close = proportional.
     */
    protected function scoreAmount(float $bankAmount, float $candidateAmount): float
    {
        $bankAmount = abs($bankAmount);
        $candidateAmount = abs($candidateAmount);

        if ($bankAmount == 0 && $candidateAmount == 0) {
            return 50.0;
        }

        // Exact match (within 1 cent)
        if (abs($bankAmount - $candidateAmount) < 0.01) {
            return 50.0;
        }

        // Close match — proportional score
        $larger = max($bankAmount, $candidateAmount);
        if ($larger == 0) {
            return 0;
        }

        $ratio = min($bankAmount, $candidateAmount) / $larger;

        if ($ratio >= 0.99) {
            return 45.0;
        }
        if ($ratio >= 0.95) {
            return 30.0;
        }

        return 0;
    }

    /**
     * Score date proximity. Same day = 30, ±1 day = 25, ±2 = 15, ±3 = 10.
     */
    protected function scoreDate(\DateTimeInterface $bankDate, $candidateDate): float
    {
        $diff = abs($bankDate->diff($candidateDate)->days);

        return match (true) {
            $diff === 0 => 30.0,
            $diff === 1 => 25.0,
            $diff === 2 => 15.0,
            $diff <= $this->dateRangeDays => 10.0,
            default => 0,
        };
    }

    /**
     * Score vendor/description similarity (max 20 points).
     */
    protected function scoreVendor(BankFeedTransaction $bankTxn, Transaction $candidate): float
    {
        $score = 0.0;
        $bankDesc = strtolower(trim($bankTxn->description));

        // Check against contact name
        if ($candidate->contact) {
            $contactName = strtolower(trim($candidate->contact->name));
            $similarity = $this->stringSimilarity($bankDesc, $contactName);

            if ($similarity >= 0.8) {
                $score = 20.0;
            } elseif ($similarity >= 0.5) {
                $score = 12.0;
            } elseif (str_contains($bankDesc, $contactName) || str_contains($contactName, $bankDesc)) {
                $score = 15.0;
            }
        }

        // Check against transaction description
        if ($candidate->description && $score < 20) {
            $txnDesc = strtolower(trim($candidate->description));
            $similarity = $this->stringSimilarity($bankDesc, $txnDesc);

            if ($similarity >= 0.8) {
                $score = max($score, 18.0);
            } elseif ($similarity >= 0.5) {
                $score = max($score, 10.0);
            } elseif (str_contains($bankDesc, $txnDesc) || str_contains($txnDesc, $bankDesc)) {
                $score = max($score, 12.0);
            }
        }

        // Check against document number (invoice/bill number)
        if ($candidate->document && $score < 20) {
            $docNumber = strtolower($candidate->document->document_number ?? '');
            if ($docNumber && str_contains($bankDesc, $docNumber)) {
                $score = max($score, 15.0);
            }
        }

        return $score;
    }

    /**
     * Calculate string similarity between two strings (0-1).
     */
    protected function stringSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    /**
     * Get human-readable reasons for a match.
     */
    protected function getMatchReasons(BankFeedTransaction $bankTxn, Transaction $candidate): array
    {
        $reasons = [];
        $amountDiff = abs(abs($bankTxn->amount) - abs($candidate->amount));
        $dateDiff = abs($bankTxn->date->diff($candidate->paid_at)->days);

        if ($amountDiff < 0.01) {
            $reasons[] = 'Exact amount match';
        } elseif ($amountDiff < abs($bankTxn->amount) * 0.05) {
            $reasons[] = 'Close amount match';
        }

        if ($dateDiff === 0) {
            $reasons[] = 'Same date';
        } elseif ($dateDiff <= $this->dateRangeDays) {
            $reasons[] = "Date within {$dateDiff} day(s)";
        }

        if ($candidate->contact) {
            $bankDesc = strtolower(trim($bankTxn->description));
            $contactName = strtolower(trim($candidate->contact->name));
            if ($this->stringSimilarity($bankDesc, $contactName) >= 0.5 ||
                str_contains($bankDesc, $contactName)) {
                $reasons[] = 'Vendor name match';
            }
        }

        if ($candidate->document) {
            $reasons[] = 'Linked to ' . ($candidate->document->type === 'invoice' ? 'Invoice' : 'Bill') .
                ' #' . $candidate->document->document_number;
        }

        return $reasons;
    }

    /**
     * Get the auto-match threshold.
     */
    public function getThreshold(): float
    {
        return $this->autoMatchThreshold;
    }
}
