<?php

namespace Modules\CreditDebitNotes\Widgets;

use App\Abstracts\Widget;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\DebitNote;

class CreditDebitNoteSummary extends Widget
{
    public $default_name = 'credit-debit-notes::general.credit_debit_summary';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        $companyId = company_id();

        $totalCreditNotes = CreditNote::where('company_id', $companyId)->count();
        $totalDebitNotes = DebitNote::where('company_id', $companyId)->count();

        $totalCreditValue = CreditNote::where('company_id', $companyId)->sum('amount');
        $totalDebitValue = DebitNote::where('company_id', $companyId)->sum('amount');

        $outstandingCredits = CreditNote::where('company_id', $companyId)
            ->whereIn('status', [CreditNote::STATUS_OPEN, CreditNote::STATUS_PARTIAL, CreditNote::STATUS_SENT])
            ->sum('amount');

        $recentCreditNotes = CreditNote::where('company_id', $companyId)
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentDebitNotes = DebitNote::where('company_id', $companyId)
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $cnByStatus = [];
        foreach (CreditNote::STATUSES as $status) {
            $cnByStatus[$status] = CreditNote::where('company_id', $companyId)
                ->where('status', $status)
                ->count();
        }

        $dnByStatus = [];
        foreach (DebitNote::STATUSES as $status) {
            $dnByStatus[$status] = DebitNote::where('company_id', $companyId)
                ->where('status', $status)
                ->count();
        }

        return $this->view('credit-debit-notes::widgets.summary', compact(
            'totalCreditNotes', 'totalDebitNotes', 'totalCreditValue', 'totalDebitValue',
            'outstandingCredits', 'recentCreditNotes', 'recentDebitNotes', 'cnByStatus', 'dnByStatus'
        ));
    }
}
