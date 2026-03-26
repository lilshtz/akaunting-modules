<?php

namespace Modules\Estimates\Widgets;

use App\Abstracts\Widget;
use Modules\Estimates\Models\Estimate;

class EstimateSummary extends Widget
{
    public $default_name = 'estimates::general.estimate_summary';

    public $default_settings = [
        'width' => 'col-md-6',
    ];

    public function show()
    {
        $companyId = company_id();

        $totalEstimates = Estimate::where('company_id', $companyId)->count();

        $totalSent = Estimate::where('company_id', $companyId)
            ->whereNotIn('status', [Estimate::STATUS_DRAFT])
            ->count();

        $totalApproved = Estimate::where('company_id', $companyId)
            ->whereIn('status', [Estimate::STATUS_APPROVED, Estimate::STATUS_CONVERTED])
            ->count();

        $approvalRate = $totalSent > 0 ? round(($totalApproved / $totalSent) * 100, 1) : 0;

        $totalConverted = Estimate::where('company_id', $companyId)
            ->where('status', Estimate::STATUS_CONVERTED)
            ->count();

        $conversionRate = $totalApproved > 0 ? round(($totalConverted / $totalApproved) * 100, 1) : 0;

        $averageValue = Estimate::where('company_id', $companyId)->avg('amount') ?? 0;

        $totalValue = Estimate::where('company_id', $companyId)->sum('amount');

        $approvedValue = Estimate::where('company_id', $companyId)
            ->whereIn('status', [Estimate::STATUS_APPROVED, Estimate::STATUS_CONVERTED])
            ->sum('amount');

        $byStatus = [];
        foreach (Estimate::STATUSES as $status) {
            $byStatus[$status] = Estimate::where('company_id', $companyId)
                ->where('status', $status)
                ->count();
        }

        $recentEstimates = Estimate::where('company_id', $companyId)
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->view('estimates::widgets.summary', compact(
            'totalEstimates', 'totalSent', 'totalApproved', 'approvalRate',
            'totalConverted', 'conversionRate', 'averageValue', 'totalValue',
            'approvedValue', 'byStatus', 'recentEstimates'
        ));
    }
}
