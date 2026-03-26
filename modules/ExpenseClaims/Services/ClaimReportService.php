<?php

namespace Modules\ExpenseClaims\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ExpenseClaims\Models\ExpenseClaim;

class ClaimReportService
{
    public function build(Request $request): array
    {
        $companyId = company_id();
        $claims = ExpenseClaim::query()
            ->where('company_id', $companyId)
            ->with(['employee.contact', 'items.category']);

        if ($request->filled('employee_id')) {
            $claims->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $claims->where('status', $request->get('status'));
        }

        if ($request->filled('date_from')) {
            $claims->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $claims->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $filteredClaimIds = (clone $claims)->pluck('id');

        $byEmployee = ExpenseClaim::query()
            ->select('employee_id', DB::raw('count(*) as claims_count'), DB::raw('sum(total) as total_amount'))
            ->where('company_id', $companyId)
            ->whereIn('id', $filteredClaimIds)
            ->with('employee.contact')
            ->groupBy('employee_id')
            ->get();

        $byCategory = DB::table('expense_claim_items as items')
            ->join('expense_claims as claims', 'claims.id', '=', 'items.claim_id')
            ->leftJoin('expense_claim_categories as categories', 'categories.id', '=', 'items.category_id')
            ->select('categories.name', DB::raw('count(items.id) as item_count'), DB::raw('sum(items.amount) as total_amount'))
            ->where('claims.company_id', $companyId)
            ->whereIn('claims.id', $filteredClaimIds)
            ->groupBy('categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $periodTotals = ExpenseClaim::query()
            ->selectRaw('DATE(created_at) as period_date, sum(total) as total_amount')
            ->where('company_id', $companyId)
            ->whereIn('id', $filteredClaimIds)
            ->groupBy('period_date')
            ->orderBy('period_date')
            ->get();

        $statusTotals = ExpenseClaim::query()
            ->select('status', DB::raw('count(*) as claims_count'), DB::raw('sum(total) as total_amount'))
            ->where('company_id', $companyId)
            ->whereIn('id', $filteredClaimIds)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pendingTotal = collect([ExpenseClaim::STATUS_SUBMITTED, ExpenseClaim::STATUS_PENDING])
            ->sum(fn ($status) => (float) optional($statusTotals->get($status))->total_amount);

        $approvedTotal = (float) optional($statusTotals->get(ExpenseClaim::STATUS_APPROVED))->total_amount
            + (float) optional($statusTotals->get(ExpenseClaim::STATUS_PAID))->total_amount;

        return compact('byEmployee', 'byCategory', 'periodTotals', 'statusTotals', 'pendingTotal', 'approvedTotal');
    }
}
