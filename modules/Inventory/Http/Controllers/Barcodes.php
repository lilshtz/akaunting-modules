<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Variant;
use Modules\Inventory\Services\BarcodeService;

class Barcodes extends Controller
{
    public function __construct(protected BarcodeService $barcodes)
    {
    }

    public function show(Request $request, int $itemId, ?int $variantId = null): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $variant = $variantId ? Variant::where('item_id', $item->id)->findOrFail($variantId) : null;

        return response()->json([
            'data' => $this->barcodes->forItem($item, $variant, $request->get('format', 'code128')),
        ]);
    }

    public function labels(Request $request, int $itemId): JsonResponse
    {
        $item = Item::where('company_id', company_id())->findOrFail($itemId);
        $variantIds = $request->get('variant_ids', []);

        if (is_string($variantIds)) {
            $variantIds = array_filter(array_map('trim', explode(',', $variantIds)));
        }

        $variantsQuery = Variant::where('item_id', $item->id)->orderBy('name');
        $variants = empty($variantIds)
            ? $variantsQuery->get()
            : $variantsQuery->whereIn('id', $variantIds)->get();

        return response()->json([
            'data' => $this->barcodes->labels($item, $variants->all(), $request->get('format', 'code128'), [
                'width' => $request->get('width'),
                'height' => $request->get('height'),
            ]),
        ]);
    }
}
