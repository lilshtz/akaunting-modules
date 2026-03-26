<?php

namespace Modules\Budgets\Widgets;

use App\Abstracts\Widget;
use Modules\Budgets\Models\Budget;
use Modules\Budgets\Services\BudgetReportService;

class BudgetSummary extends Widget
{
    public $default_name = 'budgets::general.widget_summary';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        $budget = Budget::where('company_id', company_id())
            ->with('lines.account')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
            ->orderByDesc('period_start')
            ->first();

        if (! $budget) {
            return $this->view('budgets::widgets.summary', [
                'budget' => null,
                'report' => null,
            ]);
        }

        $report = app(BudgetReportService::class)->build($budget);

        return $this->view('budgets::widgets.summary', compact('budget', 'report'));
    }
}
