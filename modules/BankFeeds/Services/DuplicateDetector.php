<?php

namespace Modules\BankFeeds\Services;

use Modules\BankFeeds\Models\BankFeedTransaction;

class DuplicateDetector
{
    /**
     * Check for duplicates within an import batch and against existing transactions.
     * Returns the count of duplicates found.
     */
    public function detectDuplicates(int $importId): int
    {
        $transactions = BankFeedTransaction::where('import_id', $importId)->get();
        $duplicateCount = 0;

        foreach ($transactions as $txn) {
            $hash = $txn->getDuplicateHashValue();
            $txn->update(['duplicate_hash' => $hash]);

            // Check for existing transactions with the same hash (from other imports)
            $existing = BankFeedTransaction::where('duplicate_hash', $hash)
                ->where('id', '!=', $txn->id)
                ->where('import_id', '!=', $importId)
                ->exists();

            if ($existing) {
                $txn->update(['is_duplicate' => true]);
                $duplicateCount++;
            }
        }

        // Also check for duplicates within the same import (same date + amount + description)
        $seen = [];
        $importTxns = BankFeedTransaction::where('import_id', $importId)
            ->orderBy('id')
            ->get();

        foreach ($importTxns as $txn) {
            $key = $txn->duplicate_hash;

            if (isset($seen[$key])) {
                if (!$txn->is_duplicate) {
                    $txn->update(['is_duplicate' => true]);
                    $duplicateCount++;
                }
            } else {
                $seen[$key] = true;
            }
        }

        return $duplicateCount;
    }

    /**
     * Check if a single transaction is a duplicate.
     */
    public function isDuplicate(BankFeedTransaction $txn): bool
    {
        $hash = $txn->getDuplicateHashValue();

        return BankFeedTransaction::where('duplicate_hash', $hash)
            ->where('id', '!=', $txn->id)
            ->exists();
    }
}
