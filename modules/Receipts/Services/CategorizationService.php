<?php

namespace Modules\Receipts\Services;

use Modules\Receipts\Models\CategorizationRule;
use Modules\Receipts\Models\Receipt;

class CategorizationService
{
    /**
     * Auto-categorize a receipt based on vendor name matching rules.
     *
     * @return array{category_id: ?int, account_id: ?int}
     */
    public function categorize(Receipt $receipt): array
    {
        $result = [
            'category_id' => null,
            'account_id' => null,
        ];

        if (empty($receipt->vendor_name)) {
            return $result;
        }

        $rules = CategorizationRule::where('company_id', $receipt->company_id)
            ->where('enabled', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesPattern($receipt->vendor_name, $rule->vendor_pattern)) {
                $result['category_id'] = $rule->category_id;
                $result['account_id'] = $rule->account_id;
                break;
            }
        }

        return $result;
    }

    /**
     * Check if vendor name matches a pattern (case-insensitive substring or wildcard match).
     */
    protected function matchesPattern(string $vendorName, string $pattern): bool
    {
        $vendorLower = strtolower($vendorName);
        $patternLower = strtolower($pattern);

        // Exact substring match
        if (str_contains($vendorLower, $patternLower)) {
            return true;
        }

        // Wildcard pattern (e.g., "home*depot" or "*amazon*")
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('\*', '.*', preg_quote($patternLower, '/')) . '$/i';
            return (bool) preg_match($regex, $vendorLower);
        }

        return false;
    }
}
