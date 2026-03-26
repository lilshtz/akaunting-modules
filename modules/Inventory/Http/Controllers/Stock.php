<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\StockUpdate;
use Modules\Inventory\Models\Stock as StockModel;
use Modules\Inventory\Services\InventoryService;

class Stock extends Controller
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockModel::query()
            ->select('inventory_stock.*')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->with(['item', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('inventory_stock.warehouse_id', (int) $request->get('warehouse_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('inventory_stock.item_id', (int) $request->get('item_id'));
        }

        if ($request->boolean('low_stock')) {
            $query->whereNotNull('inventory_stock.reorder_level')
                ->whereColumn('inventory_stock.quantity', '<', 'inventory_stock.reorder_level');
        }

        return response()->json(['data' => $query->orderBy('inventory_stock.id', 'desc')->get()]);
    }

    public function update(StockUpdate $request): JsonResponse
    {
        $stock = $this->inventory->setStockLevel(
            company_id(),
            (int) $request->get('item_id'),
            (int) $request->get('warehouse_id'),
            (float) $request->get('quantity'),
            $request->filled('reorder_level') ? (float) $request->get('reorder_level') : null,
            $request->get('description')
        );

        return response()->json([
            'message' => trans('inventory::general.stock_updated'),
            'data' => $stock,
        ]);
    }

    public function item(int $itemId): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $stock = StockModel::query()
            ->select('inventory_stock.*')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->where('inventory_warehouses.company_id', company_id())
            ->where('inventory_stock.item_id', $itemId)
            ->with('warehouse')
            ->get();

        return response()->json([
            'data' => [
                'item' => $item,
                'stock' => $stock,
            ],
        ]);
    }

    public function alerts(): JsonResponse
    {
        return response()->json([
            'data' => $this->inventory->lowStock(company_id()),
        ]);
    }
}
