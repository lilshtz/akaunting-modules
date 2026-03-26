<?php

namespace Modules\Receipts\Widgets;

use App\Abstracts\Widget;
use Modules\Receipts\Models\Receipt;

class ReceiptSummary extends Widget
{
    public $default_name = 'receipts::general.receipt_summary';

    public $default_settings = [
        'width' => 'col-md-4',
    ];

    public function show()
    {
        $companyId = company_id();

        $uploadedCount = Receipt::where('company_id', $companyId)
            ->where('status', Receipt::STATUS_UPLOADED)
            ->count();

        $reviewedCount = Receipt::where('company_id', $companyId)
            ->where('status', Receipt::STATUS_REVIEWED)
            ->count();

        $processedCount = Receipt::where('company_id', $companyId)
            ->where('status', Receipt::STATUS_PROCESSED)
            ->count();

        $totalPending = $uploadedCount + $reviewedCount;

        $stats = [
            'uploaded' => $uploadedCount,
            'reviewed' => $reviewedCount,
            'processed' => $processedCount,
            'total_pending' => $totalPending,
        ];

        return $this->view('receipts::widgets.summary', compact('stats'));
    }
}
