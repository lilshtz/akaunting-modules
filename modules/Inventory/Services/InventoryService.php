<?php

namespace Modules\Inventory\Services;

use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Adjustment;
use Modules\Inventory\Models\History;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\TransferOrder;
use Modules\Inventory\Models\Variant;
use Modules\Inventory\Models\Warehouse;

class InventoryService
{
    public function getOrCreateDefaultWarehouse(int $companyId): Warehouse
    {
        $warehouse = Warehouse::where('company_id', $companyId)
            ->enabled()
            ->orderBy('id')
            ->first();

        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::create([
            'company_id' => $companyId,
            'name' => trans('inventory::general.default_warehouse'),
            'enabled' => true,
        ]);
    }

    public function setStockLevel(
        int $companyId,
        int $itemId,
        ?int $variantId,
        int $warehouseId,
        float $quantity,
        ?float $reorderLevel = null,
        ?string $description = null
    ): Stock {
        return DB::transaction(function () use ($companyId, $itemId, $variantId, $warehouseId, $quantity, $reorderLevel, $description) {
            $stock = Stock::lockForUpdate()
                ->where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->when($variantId, fn ($query) => $query->where('variant_id', $variantId), fn ($query) => $query->whereNull('variant_id'))
                ->first() ?: new Stock([
                    'item_id' => $itemId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                ]);

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $change = round($quantity - $currentQuantity, 4);

            $stock->quantity = $quantity;
            $stock->reorder_level = $reorderLevel;
            $stock->save();

            if ($change != 0.0) {
                $this->createHistory([
                    'company_id' => $companyId,
                    'item_id' => $itemId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => $change,
                    'type' => 'adjustment',
                    'reference_type' => 'manual_adjustment',
                    'reference_id' => null,
                    'description' => $description ?: 'Manual stock adjustment',
                    'date' => now(),
                ]);
            }

            return $stock->fresh(['item', 'variant', 'warehouse']);
        });
    }

    public function adjustStock(
        int $companyId,
        int $itemId,
        ?int $variantId,
        int $warehouseId,
        float $quantityChange,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        $date = null
    ): Stock {
        return DB::transaction(function () use ($companyId, $itemId, $variantId, $warehouseId, $quantityChange, $type, $referenceType, $referenceId, $description, $date) {
            $stock = Stock::lockForUpdate()
                ->where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->when($variantId, fn ($query) => $query->where('variant_id', $variantId), fn ($query) => $query->whereNull('variant_id'))
                ->first();

            if (! $stock) {
                $stock = Stock::create([
                    'item_id' => $itemId,
                    'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                ]);
            }

            $stock->quantity = round(((float) $stock->quantity) + $quantityChange, 4);
            $stock->save();

            $this->createHistory([
                'company_id' => $companyId,
                'item_id' => $itemId,
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'quantity_change' => $quantityChange,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => $date ?: now(),
            ]);

            return $stock->fresh(['item', 'variant', 'warehouse']);
        });
    }

    public function applyDocument(Document $document, bool $reverse = false): void
    {
        if (! in_array($document->type, [Document::INVOICE_TYPE, Document::BILL_TYPE], true)) {
            return;
        }

        $referenceType = $reverse ? 'document_deleted' : 'document_created';

        if ($this->documentAlreadyApplied($document->id, $referenceType)) {
            return;
        }

        $warehouse = $this->getOrCreateDefaultWarehouse((int) $document->company_id);
        $items = $document->relationLoaded('items') ? $document->items : $document->items()->get();

        foreach ($items as $item) {
            if (! ($item instanceof DocumentItem) || empty($item->item_id) || empty($item->quantity)) {
                continue;
            }

            $quantity = $this->resolveQuantityChange($document->type, (float) $item->quantity, $reverse);

            if ($quantity == 0.0) {
                continue;
            }

            $this->adjustStock(
                (int) $document->company_id,
                (int) $item->item_id,
                null,
                (int) $warehouse->id,
                $quantity,
                $document->type === Document::INVOICE_TYPE ? 'sale' : 'purchase',
                $referenceType,
                (int) $document->id,
                $this->buildDocumentDescription($document, $quantity, $reverse),
                $document->issued_at ?? $document->created_at ?? now()
            );
        }
    }

    public function lowStock(int $companyId): Collection
    {
        return Stock::query()
            ->select('inventory_stock.*')
            ->join('inventory_warehouses', 'inventory_warehouses.id', '=', 'inventory_stock.warehouse_id')
            ->where('inventory_warehouses.company_id', $companyId)
            ->whereNotNull('inventory_stock.reorder_level')
            ->whereColumn('inventory_stock.quantity', '<', 'inventory_stock.reorder_level')
            ->with(['item', 'variant', 'warehouse'])
            ->get();
    }

    public function createAdjustment(
        int $companyId,
        Warehouse $warehouse,
        \App\Models\Common\Item $item,
        ?Variant $variant,
        float $quantity,
        string $reason,
        ?string $description = null,
        $date = null,
        ?int $userId = null
    ): Adjustment {
        return DB::transaction(function () use ($companyId, $warehouse, $item, $variant, $quantity, $reason, $description, $date, $userId) {
            $adjustment = Adjustment::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse->id,
                'item_id' => $item->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'reason' => $reason,
                'description' => $description,
                'date' => $date ?: now(),
                'user_id' => $userId ?: 1,
            ]);

            $this->adjustStock(
                $companyId,
                (int) $item->id,
                $variant?->id,
                (int) $warehouse->id,
                $quantity,
                'adjustment',
                'inventory_adjustment',
                (int) $adjustment->id,
                trim(implode(' - ', array_filter([ucfirst($reason), $description])))
            );

            return $adjustment;
        });
    }

    public function createTransferOrder(int $companyId, array $payload, string $status = 'draft'): TransferOrder
    {
        return DB::transaction(function () use ($companyId, $payload, $status) {
            $order = TransferOrder::create([
                'company_id' => $companyId,
                'from_warehouse_id' => $payload['from_warehouse_id'],
                'to_warehouse_id' => $payload['to_warehouse_id'],
                'status' => $status,
                'date' => $payload['date'] ?: now(),
                'description' => $payload['description'] ?? null,
            ]);

            $order->items()->createMany($payload['items']);

            return $order;
        });
    }

    public function updateTransferOrder(TransferOrder $order, array $payload, string $status): TransferOrder
    {
        if ($order->status === 'received') {
            return $order;
        }

        return DB::transaction(function () use ($order, $payload, $status) {
            $order->update([
                'from_warehouse_id' => $payload['from_warehouse_id'],
                'to_warehouse_id' => $payload['to_warehouse_id'],
                'status' => $status,
                'date' => $payload['date'] ?: $order->date,
                'description' => $payload['description'] ?? null,
            ]);

            $order->items()->delete();
            $order->items()->createMany($payload['items']);

            return $order->fresh(['items']);
        });
    }

    public function shipTransferOrder(TransferOrder $order): TransferOrder
    {
        if ($order->status !== 'draft') {
            return $order;
        }

        return DB::transaction(function () use ($order) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $this->adjustStock(
                    (int) $order->company_id,
                    (int) $item->item_id,
                    $item->variant_id ? (int) $item->variant_id : null,
                    (int) $order->from_warehouse_id,
                    -1 * (float) $item->quantity,
                    'transfer_out',
                    'transfer_order',
                    (int) $order->id,
                    'Transfer shipped'
                );
            }

            $order->update(['status' => 'in_transit']);

            return $order->fresh(['items']);
        });
    }

    public function receiveTransferOrder(TransferOrder $order): TransferOrder
    {
        if ($order->status === 'received') {
            return $order;
        }

        return DB::transaction(function () use ($order) {
            if ($order->status === 'draft') {
                $order = $this->shipTransferOrder($order);
            }

            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $this->adjustStock(
                    (int) $order->company_id,
                    (int) $item->item_id,
                    $item->variant_id ? (int) $item->variant_id : null,
                    (int) $order->to_warehouse_id,
                    (float) $item->quantity,
                    'transfer_in',
                    'transfer_order',
                    (int) $order->id,
                    'Transfer received'
                );
            }

            $order->update(['status' => 'received']);

            return $order->fresh(['items']);
        });
    }

    protected function documentAlreadyApplied(int $documentId, string $referenceType): bool
    {
        return History::where('reference_type', $referenceType)
            ->where('reference_id', $documentId)
            ->exists();
    }

    protected function resolveQuantityChange(string $documentType, float $quantity, bool $reverse): float
    {
        $multiplier = $documentType === Document::INVOICE_TYPE ? -1 : 1;

        if ($reverse) {
            $multiplier *= -1;
        }

        return round($quantity * $multiplier, 4);
    }

    protected function buildDocumentDescription(Document $document, float $quantityChange, bool $reverse): string
    {
        $verb = $document->type === Document::INVOICE_TYPE ? 'Invoice' : 'Bill';
        $action = $reverse ? 'reversed' : 'applied';

        return sprintf('%s %s stock movement for %s (%0.4f)', $verb, $action, $document->document_number, $quantityChange);
    }

    protected function createHistory(array $attributes): History
    {
        return History::create($attributes);
    }
}
