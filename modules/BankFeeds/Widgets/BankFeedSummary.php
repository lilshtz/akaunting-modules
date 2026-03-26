<?php

namespace Modules\BankFeeds\Widgets;

use App\Abstracts\Widget;
use App\Models\Banking\Account;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Models\BankFeedReconciliation;

class BankFeedSummary extends Widget
{
    public $default_name = 'bank-feeds::general.bank_feed_summary';

    public $default_settings = [
        'width' => 'col-md-4',
    ];

    public function show()
    {
        $companyId = company_id();

        $companyScope = function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        };

        $pendingCount = BankFeedTransaction::whereHas('import', $companyScope)
            ->where('status', BankFeedTransaction::STATUS_PENDING)
            ->count();

        $categorizedCount = BankFeedTransaction::whereHas('import', $companyScope)
            ->where('status', BankFeedTransaction::STATUS_CATEGORIZED)
            ->count();

        $unmatchedCount = BankFeedTransaction::whereHas('import', $companyScope)
            ->whereIn('status', [BankFeedTransaction::STATUS_PENDING, BankFeedTransaction::STATUS_CATEGORIZED])
            ->count();

        $totalImports = BankFeedImport::where('company_id', $companyId)
            ->where('status', BankFeedImport::STATUS_COMPLETE)
            ->count();

        // Count accounts with unreconciled transactions
        $accountsWithTransactions = BankFeedTransaction::whereHas('import', $companyScope)
            ->whereIn('status', [BankFeedTransaction::STATUS_PENDING, BankFeedTransaction::STATUS_CATEGORIZED])
            ->distinct('bank_account_id')
            ->count('bank_account_id');

        $stats = [
            'pending' => $pendingCount,
            'categorized' => $categorizedCount,
            'unmatched' => $unmatchedCount,
            'total_imports' => $totalImports,
            'unreconciled_accounts' => $accountsWithTransactions,
        ];

        return $this->view('bank-feeds::widgets.summary', compact('stats'));
    }
}
