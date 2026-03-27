<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Models\CrmDeal;
use Modules\Crm\Models\CrmPipelineStage;

class DealReports extends Controller
{
    public function index(Request $request): Response
    {
        CrmPipelineStage::ensureDefaults(company_id());

        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->endOfMonth()->format('Y-m-d'));

        $valueByStage = CrmPipelineStage::forCompany(company_id())
            ->ordered()
            ->withSum(['deals as open_value' => fn ($query) => $query->where('status', CrmDeal::STATUS_OPEN)], 'value')
            ->withCount(['deals as open_count' => fn ($query) => $query->where('status', CrmDeal::STATUS_OPEN)])
            ->get();

        $closedBase = CrmDeal::forCompany(company_id())
            ->visible()
            ->whereIn('status', [CrmDeal::STATUS_WON, CrmDeal::STATUS_LOST])
            ->whereBetween(DB::raw('DATE(closed_at)'), [$from, $to]);

        $closedCount = (clone $closedBase)->count();
        $wonCount = (clone $closedBase)->where('status', CrmDeal::STATUS_WON)->count();
        $lostCount = (clone $closedBase)->where('status', CrmDeal::STATUS_LOST)->count();

        $wonLostByPeriod = (clone $closedBase)
            ->selectRaw('status, COUNT(*) as deal_count, COALESCE(SUM(value), 0) as total_value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $growthReport = CrmDeal::forCompany(company_id())
            ->visible()
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as created_count, COALESCE(SUM(value), 0) as created_value")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $wonGrowth = CrmDeal::forCompany(company_id())
            ->visible()
            ->where('status', CrmDeal::STATUS_WON)
            ->whereBetween(DB::raw('DATE(closed_at)'), [$from, $to])
            ->selectRaw("DATE_FORMAT(closed_at, '%Y-%m') as month_key, COUNT(*) as won_count, COALESCE(SUM(value), 0) as won_value")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        return view('crm::reports.index', compact(
            'from',
            'to',
            'valueByStage',
            'closedCount',
            'wonCount',
            'lostCount',
            'wonLostByPeriod',
            'growthReport',
            'wonGrowth'
        ));
    }
}
