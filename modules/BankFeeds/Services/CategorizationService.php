<?php

namespace Modules\BankFeeds\Services;

use Modules\BankFeeds\Models\BankFeedRule;
use Modules\BankFeeds\Models\BankFeedTransaction;

class CategorizationService
{
    /**
     * Apply categorization rules to a single transaction.
     */
    public function categorize(BankFeedTransaction $transaction, int $companyId): bool
    {
        $rules = BankFeedRule::where('company_id', $companyId)
            ->enabled()
            ->ordered()
            ->get();

        foreach ($rules as $rule) {
            if ($rule->matches($transaction)) {
                $updates = ['status' => BankFeedTransaction::STATUS_CATEGORIZED];

                if ($rule->category_id) {
                    $updates['category_id'] = $rule->category_id;
                }

                if ($rule->vendor_id) {
                    $updates['vendor_id'] = $rule->vendor_id;
                }

                $transaction->update($updates);
                return true;
            }
        }

        return false;
    }

    /**
     * Apply categorization rules to all transactions in an import.
     */
    public function categorizeImport(int $importId, int $companyId): int
    {
        $transactions = BankFeedTransaction::where('import_id', $importId)
            ->pending()
            ->get();

        $categorized = 0;

        foreach ($transactions as $transaction) {
            if ($this->categorize($transaction, $companyId)) {
                $categorized++;
            }
        }

        return $categorized;
    }

    /**
     * Re-apply rules to all uncategorized transactions for a company.
     */
    public function bulkCategorize(int $companyId): int
    {
        $transactions = BankFeedTransaction::whereHas('import', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('status', BankFeedTransaction::STATUS_PENDING)->get();

        $categorized = 0;

        foreach ($transactions as $transaction) {
            if ($this->categorize($transaction, $companyId)) {
                $categorized++;
            }
        }

        return $categorized;
    }
}
