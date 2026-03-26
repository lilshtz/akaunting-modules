<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class Reports extends Controller
{
    public function status(): JsonResponse
    {
        $rows = DB::table('inventory_stock')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->join('items', 'items.id', '=', 'inventory_stock.item_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->select([
                'inventory_stock.id',
                'items.id as item_id',
                'items.name as item_name',
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
                'inventory_stock.quantity',
                'inventory_stock.reorder_level',
                DB::raw('CASE WHEN inventory_stock.reorder_level IS NOT NULL AND inventory_stock.quantity < inventory_stock.reorder_level THEN 1 ELSE 0 END as is_low_stock'),
            ])
            ->orderBy('items.name')
            ->orderBy('inventory_warehouses.name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function value(): JsonResponse
    {
        $rows = DB::table('inventory_stock')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->join('items', 'items.id', '=', 'inventory_stock.item_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->select([
                'items.id as item_id',
                'items.name as item_name',
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
                'inventory_stock.quantity',
                'items.purchase_price as cost',
                DB::raw('ROUND(inventory_stock.quantity * COALESCE(items.purchase_price, 0), 4) as stock_value'),
            ])
            ->orderBy('items.name')
            ->orderBy('inventory_warehouses.name')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
