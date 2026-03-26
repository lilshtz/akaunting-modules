<?php

namespace Modules\ExpenseClaims\Widgets;

use App\Abstracts\Widget;
use Modules\ExpenseClaims\Models\ExpenseClaim;

class ExpenseClaimSummary extends Widget
{
    public $default_name = 'expense-claims::general.widget_summary';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        $companyId = company_id();

        $pendingCount = ExpenseClaim::where('company_id', $companyId)->pendingApproval()->count();
        $approvedTotal = ExpenseClaim::where('company_id', $companyId)
            ->whereIn('status', [ExpenseClaim::STATUS_APPROVED, ExpenseClaim::STATUS_PAID])
            ->sum('total');
        $reimbursableTotal = ExpenseClaim::where('company_id', $companyId)
            ->whereIn('status', [ExpenseClaim::STATUS_APPROVED, ExpenseClaim::STATUS_PAID])
            ->sum('reimbursable_total');
        $recentClaims = ExpenseClaim::where('company_id', $companyId)
            ->with('employee.contact')
            ->latest()
            ->limit(5)
            ->get();

        return $this->view('expense-claims::widgets.summary', compact(
            'pendingCount', 'approvedTotal', 'reimbursableTotal', 'recentClaims'
        ));
    }
}
