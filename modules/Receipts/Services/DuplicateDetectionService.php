<?php

namespace Modules\Receipts\Services;

use Illuminate\Support\Collection;
use Modules\Receipts\Models\Receipt;

class DuplicateDetectionService
{
    /**
     * Check for potential duplicate receipts.
     * Matches on same vendor + amount + date within ±3 days.
     */
    public function findDuplicates(int $companyId, ?string $vendorName, ?float $amount, ?string $date, ?int $excludeId = null): Collection
    {
        if (empty($vendorName) && empty($amount)) {
            return collect();
        }

        $query = Receipt::where('company_id', $companyId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($vendorName) {
            $query->where('vendor_name', 'like', '%' . $vendorName . '%');
        }

        if ($amount !== null) {
            $query->where('amount', $amount);
        }

        if ($date) {
            $dateObj = \Carbon\Carbon::parse($date);
            $query->whereBetween('receipt_date', [
                $dateObj->copy()->subDays(3)->toDateString(),
                $dateObj->copy()->addDays(3)->toDateString(),
            ]);
        }

        return $query->get();
    }

    /**
     * Check if a receipt likely has duplicates.
     */
    public function hasDuplicates(Receipt $receipt): bool
    {
        return $this->findDuplicates(
            $receipt->company_id,
            $receipt->vendor_name,
            $receipt->amount,
            $receipt->receipt_date?->toDateString(),
            $receipt->id
        )->isNotEmpty();
    }
}
