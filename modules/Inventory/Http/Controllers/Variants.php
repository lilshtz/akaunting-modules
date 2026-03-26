<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Requests\VariantStore;
use Modules\Inventory\Http\Requests\VariantUpdate;
use Modules\Inventory\Models\Variant;

class Variants extends Controller
{
    public function index(int $itemId): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);

        return response()->json([
            'data' => Variant::where('item_id', $item->id)
                ->with('stock.warehouse')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(VariantStore $request, int $itemId): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);

        $variant = Variant::create([
            'item_id' => $item->id,
            'name' => $request->get('name'),
            'sku' => $request->get('sku'),
            'attributes_json' => $request->get('attributes_json'),
            'cost_price' => $request->get('cost_price'),
            'sale_price' => $request->get('sale_price'),
        ]);

        return response()->json([
            'message' => trans('messages.success.added', ['type' => $variant->name]),
            'data' => $variant->fresh(),
        ], 201);
    }

    public function show(int $itemId, int $variant): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $variant = Variant::where('item_id', $item->id)
            ->with(['stock.warehouse', 'histories.warehouse'])
            ->findOrFail($variant);

        return response()->json(['data' => $variant]);
    }

    public function update(VariantUpdate $request, int $itemId, int $variant): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $variant = Variant::where('item_id', $item->id)->findOrFail($variant);

        $variant->update([
            'name' => $request->get('name'),
            'sku' => $request->get('sku'),
            'attributes_json' => $request->get('attributes_json'),
            'cost_price' => $request->get('cost_price'),
            'sale_price' => $request->get('sale_price'),
        ]);

        return response()->json([
            'message' => trans('messages.success.updated', ['type' => $variant->name]),
            'data' => $variant->fresh(),
        ]);
    }

    public function destroy(int $itemId, int $variant): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $variant = Variant::where('item_id', $item->id)->findOrFail($variant);
        $name = $variant->name;

        $variant->delete();

        return response()->json([
            'message' => trans('messages.success.deleted', ['type' => $name]),
        ]);
    }
}
