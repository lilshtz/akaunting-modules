<?php

namespace Modules\BankFeeds\Widgets;

use App\Abstracts\Widget;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Modules\BankFeeds\Models\BankFeedImport;

class BankFeedSummary extends Widget
{
    public $default_name = 'bank-feeds::general.bank_feed_summary';

    public $default_settings = [
        'width' => 'col-md-4',
    ];

    public function show()
    {
        $companyId = company_id();

        $pendingCount = BankFeedTransaction::whereHas('import', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('status', BankFeedTransaction::STATUS_PENDING)->count();

        $categorizedCount = BankFeedTransaction::whereHas('import', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('status', BankFeedTransaction::STATUS_CATEGORIZED)->count();

        $totalImports = BankFeedImport::where('company_id', $companyId)
            ->where('status', BankFeedImport::STATUS_COMPLETE)
            ->count();

        $stats = [
            'pending' => $pendingCount,
            'categorized' => $categorizedCount,
            'total_imports' => $totalImports,
        ];

        return $this->view('bank-feeds::widgets.summary', compact('stats'));
    }
}
