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
            ->leftJoin('inventory_variants', 'inventory_variants.id', '=', 'inventory_stock.variant_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->select([
                'inventory_stock.id',
                'items.id as item_id',
                'items.name as item_name',
                'inventory_variants.id as variant_id',
                'inventory_variants.name as variant_name',
                'inventory_variants.sku as variant_sku',
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
            ->leftJoin('inventory_variants', 'inventory_variants.id', '=', 'inventory_stock.variant_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->select([
                'items.id as item_id',
                'items.name as item_name',
                'inventory_variants.id as variant_id',
                'inventory_variants.name as variant_name',
                'inventory_variants.sku as variant_sku',
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
                'inventory_stock.quantity',
                DB::raw('COALESCE(inventory_variants.cost_price, items.purchase_price, 0) as cost'),
                DB::raw('ROUND(inventory_stock.quantity * COALESCE(inventory_variants.cost_price, items.purchase_price, 0), 4) as stock_value'),
            ])
            ->orderBy('items.name')
            ->orderBy('inventory_warehouses.name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function variants(): JsonResponse
    {
        $rows = DB::table('inventory_stock')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->join('items', 'items.id', '=', 'inventory_stock.item_id')
            ->leftJoin('inventory_variants', 'inventory_variants.id', '=', 'inventory_stock.variant_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->select([
                'items.id as item_id',
                'items.name as item_name',
                'inventory_variants.id as variant_id',
                'inventory_variants.name as variant_name',
                'inventory_variants.sku',
                'inventory_variants.attributes_json',
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
                'inventory_stock.quantity',
                'inventory_stock.reorder_level',
            ])
            ->orderBy('items.name')
            ->orderBy('inventory_variants.name')
            ->orderBy('inventory_warehouses.name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function adjustments(): JsonResponse
    {
        $rows = DB::table('inventory_adjustments')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_adjustments.warehouse_id')
            ->join('items', 'items.id', '=', 'inventory_adjustments.item_id')
            ->leftJoin('inventory_variants', 'inventory_variants.id', '=', 'inventory_adjustments.variant_id')
            ->where('inventory_adjustments.company_id', company_id())
            ->select([
                'inventory_adjustments.id',
                'inventory_adjustments.reason',
                'inventory_adjustments.quantity',
                'inventory_adjustments.description',
                'inventory_adjustments.date',
                'items.id as item_id',
                'items.name as item_name',
                'inventory_variants.id as variant_id',
                'inventory_variants.name as variant_name',
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
            ])
            ->orderByDesc('inventory_adjustments.date')
            ->orderByDesc('inventory_adjustments.id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function transfers(): JsonResponse
    {
        $rows = DB::table('inventory_transfer_orders')
            ->join('inventory_warehouses as from_warehouse', 'from_warehouse.id', '=', 'inventory_transfer_orders.from_warehouse_id')
            ->join('inventory_warehouses as to_warehouse', 'to_warehouse.id', '=', 'inventory_transfer_orders.to_warehouse_id')
            ->leftJoin('inventory_transfer_items', 'inventory_transfer_items.transfer_order_id', '=', 'inventory_transfer_orders.id')
            ->leftJoin('items', 'items.id', '=', 'inventory_transfer_items.item_id')
            ->leftJoin('inventory_variants', 'inventory_variants.id', '=', 'inventory_transfer_items.variant_id')
            ->where('inventory_transfer_orders.company_id', company_id())
            ->select([
                'inventory_transfer_orders.id',
                'inventory_transfer_orders.status',
                'inventory_transfer_orders.date',
                'inventory_transfer_orders.description',
                'from_warehouse.name as from_warehouse_name',
                'to_warehouse.name as to_warehouse_name',
                'items.id as item_id',
                'items.name as item_name',
                'inventory_variants.id as variant_id',
                'inventory_variants.name as variant_name',
                'inventory_transfer_items.quantity',
            ])
            ->orderByDesc('inventory_transfer_orders.date')
            ->orderByDesc('inventory_transfer_orders.id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function itemGroups(): JsonResponse
    {
        $rows = DB::table('inventory_item_groups')
            ->leftJoin('inventory_item_group_items', 'inventory_item_group_items.item_group_id', '=', 'inventory_item_groups.id')
            ->leftJoin('items', 'items.id', '=', 'inventory_item_group_items.item_id')
            ->where('inventory_item_groups.company_id', company_id())
            ->select([
                'inventory_item_groups.id',
                'inventory_item_groups.name',
                'inventory_item_groups.description',
                DB::raw('COUNT(items.id) as items_count'),
                DB::raw("GROUP_CONCAT(items.name ORDER BY items.name SEPARATOR ', ') as item_names"),
            ])
            ->groupBy('inventory_item_groups.id', 'inventory_item_groups.name', 'inventory_item_groups.description')
            ->orderBy('inventory_item_groups.name')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
