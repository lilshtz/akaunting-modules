<?php

namespace Modules\Inventory\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\TransferOrderStore;
use Modules\Inventory\Http\Requests\TransferOrderUpdate;
use Modules\Inventory\Models\TransferOrder;
use Modules\Inventory\Models\Variant;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryService;

class TransferOrders extends Controller
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = TransferOrder::where('company_id', company_id())
            ->with(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return response()->json([
            'data' => $query->orderByDesc('date')->orderByDesc('id')->get(),
        ]);
    }

    public function store(TransferOrderStore $request): JsonResponse
    {
        $order = $this->inventory->createTransferOrder(
            company_id(),
            $this->validatedTransferPayload($request),
            $request->get('status', 'draft')
        );

        return response()->json([
            'message' => trans('messages.success.added', ['type' => trans('inventory::general.transfer_order')]),
            'data' => $order->load(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = TransferOrder::where('company_id', company_id())
            ->with(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant'])
            ->findOrFail($id);

        return response()->json(['data' => $order]);
    }

    public function update(TransferOrderUpdate $request, int $id): JsonResponse
    {
        $order = TransferOrder::where('company_id', company_id())->findOrFail($id);
        $order = $this->inventory->updateTransferOrder($order, $this->validatedTransferPayload($request), $request->get('status', $order->status));

        return response()->json([
            'message' => trans('messages.success.updated', ['type' => trans('inventory::general.transfer_order')]),
            'data' => $order->load(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant']),
        ]);
    }

    public function ship(int $id): JsonResponse
    {
        $order = TransferOrder::where('company_id', company_id())->findOrFail($id);
        $order = $this->inventory->shipTransferOrder($order);

        return response()->json([
            'message' => trans('inventory::general.transfer_shipped'),
            'data' => $order->load(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant']),
        ]);
    }

    public function receive(int $id): JsonResponse
    {
        $order = TransferOrder::where('company_id', company_id())->findOrFail($id);
        $order = $this->inventory->receiveTransferOrder($order);

        return response()->json([
            'message' => trans('inventory::general.transfer_received'),
            'data' => $order->load(['fromWarehouse', 'toWarehouse', 'items.item', 'items.variant']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $order = TransferOrder::where('company_id', company_id())->findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => trans('messages.success.deleted', ['type' => trans('inventory::general.transfer_order')]),
        ]);
    }

    protected function validatedTransferPayload(Request $request): array
    {
        $fromWarehouse = Warehouse::where('company_id', company_id())->findOrFail((int) $request->get('from_warehouse_id'));
        $toWarehouse = Warehouse::where('company_id', company_id())->findOrFail((int) $request->get('to_warehouse_id'));
        $items = [];

        foreach ($request->get('items', []) as $entry) {
            $item = Item::where('company_id', company_id())->findOrFail((int) $entry['item_id']);
            $variant = ! empty($entry['variant_id'])
                ? Variant::where('item_id', $item->id)->findOrFail((int) $entry['variant_id'])
                : null;

            $items[] = [
                'item_id' => $item->id,
                'variant_id' => $variant?->id,
                'quantity' => (float) $entry['quantity'],
            ];
        }

        return [
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'date' => $request->get('date'),
            'description' => $request->get('description'),
            'items' => $items,
        ];
    }
}
