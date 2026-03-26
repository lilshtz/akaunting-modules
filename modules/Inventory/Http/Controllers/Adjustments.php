<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\AdjustmentStore;
use Modules\Inventory\Models\Adjustment;
use Modules\Inventory\Models\Variant;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;

class Adjustments extends Controller
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Adjustment::where('company_id', company_id())
            ->with(['item', 'variant', 'warehouse', 'user']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->get('warehouse_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->get('item_id'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->get('reason'));
        }

        return response()->json([
            'data' => $query->orderByDesc('date')->orderByDesc('id')->get(),
        ]);
    }

    public function store(AdjustmentStore $request): JsonResponse
    {
        $warehouse = Warehouse::where('company_id', company_id())->findOrFail((int) $request->get('warehouse_id'));
        $item = Item::where('company_id', company_id())->findOrFail((int) $request->get('item_id'));
        $variant = $request->filled('variant_id')
            ? Variant::where('item_id', $item->id)->findOrFail((int) $request->get('variant_id'))
            : null;

        $adjustment = $this->inventory->createAdjustment(
            company_id(),
            $warehouse,
            $item,
            $variant,
            (float) $request->get('quantity'),
            $request->get('reason'),
            $request->get('description'),
            $request->get('date'),
            auth()->id()
        );

        return response()->json([
            'message' => trans('messages.success.added', ['type' => trans('inventory::general.adjustment')]),
            'data' => $adjustment->load(['item', 'variant', 'warehouse', 'user']),
        ], 201);
    }
}
