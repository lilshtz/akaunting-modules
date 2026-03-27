<?php

namespace Modules\Pos\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Pos\Models\PosOrder;

class Reports extends Controller
{
    public function daily(Request $request): Response|mixed
    {
        $date = $request->get('date', now()->toDateString());

        $orders = PosOrder::ownedByCompany()
            ->with(['items', 'contact'])
            ->whereDate('created_at', $date)
            ->latest()
            ->get();

        $paymentSummary = PosOrder::ownedByCompany()
            ->select('payment_method', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_amount'))
            ->whereDate('created_at', $date)
            ->groupBy('payment_method')
            ->get();

        $summary = [
            'date' => $date,
            'order_count' => $orders->count(),
            'gross_sales' => (float) $orders->where('status', PosOrder::STATUS_COMPLETED)->sum('total'),
            'refunds' => abs((float) $orders->where('status', PosOrder::STATUS_REFUNDED)->sum('total')),
            'net_sales' => (float) $orders->sum('total'),
            'tax' => (float) $orders->sum('tax'),
            'discount' => (float) $orders->sum('discount'),
        ];

        return view('pos::reports.daily', compact('orders', 'paymentSummary', 'summary'));
    }
}
