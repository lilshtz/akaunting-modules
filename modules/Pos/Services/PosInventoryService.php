<?php

namespace Modules\Pos\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\History;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Pos\Models\PosOrder;

class PosInventoryService
{
    public function enabled(): bool
    {
        return class_exists(Stock::class)
            && Schema::hasTable('inventory_stock')
            && Schema::hasTable('inventory_history');
    }

    public function stockLevels(Collection $items): array
    {
        if (! $this->enabled() || $items->isEmpty()) {
            return [];
        }

        $itemIds = $items->pluck('id')->filter()->all();

        if (empty($itemIds)) {
            return [];
        }

        return Stock::query()
            ->select('item_id', DB::raw('SUM(quantity) as quantity'))
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->pluck('quantity', 'item_id')
            ->map(fn ($quantity) => (float) $quantity)
            ->all();
    }

    public function deduct(PosOrder $order): void
    {
        if (! $this->enabled()) {
            return;
        }

        $warehouse = $this->defaultWarehouse($order->company_id);

        if (! $warehouse) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->item_id) {
                continue;
            }

            $stock = Stock::firstOrCreate([
                'item_id' => $item->item_id,
                'variant_id' => null,
                'warehouse_id' => $warehouse->id,
            ], [
                'quantity' => 0,
            ]);

            $stock->quantity = (float) $stock->quantity - (float) $item->quantity;
            $stock->save();

            History::create([
                'company_id' => $order->company_id,
                'item_id' => $item->item_id,
                'variant_id' => null,
                'warehouse_id' => $warehouse->id,
                'quantity_change' => -1 * (float) $item->quantity,
                'type' => 'pos_sale',
                'reference_type' => PosOrder::class,
                'reference_id' => $order->id,
                'description' => trans('pos::general.messages.inventory_deducted', ['order' => $order->order_number]),
                'date' => $order->created_at ?? now(),
            ]);
        }
    }

    public function restore(PosOrder $refundOrder): void
    {
        if (! $this->enabled()) {
            return;
        }

        $warehouse = $this->defaultWarehouse($refundOrder->company_id);

        if (! $warehouse) {
            return;
        }

        foreach ($refundOrder->items as $item) {
            if (! $item->item_id) {
                continue;
            }

            $stock = Stock::firstOrCreate([
                'item_id' => $item->item_id,
                'variant_id' => null,
                'warehouse_id' => $warehouse->id,
            ], [
                'quantity' => 0,
            ]);

            $restored = abs((float) $item->quantity);
            $stock->quantity = (float) $stock->quantity + $restored;
            $stock->save();

            History::create([
                'company_id' => $refundOrder->company_id,
                'item_id' => $item->item_id,
                'variant_id' => null,
                'warehouse_id' => $warehouse->id,
                'quantity_change' => $restored,
                'type' => 'pos_refund',
                'reference_type' => PosOrder::class,
                'reference_id' => $refundOrder->id,
                'description' => trans('pos::general.messages.inventory_restored', ['order' => $refundOrder->order_number]),
                'date' => $refundOrder->created_at ?? now(),
            ]);
        }
    }

    protected function defaultWarehouse(int $companyId): ?Warehouse
    {
        if (! class_exists(Warehouse::class) || ! Schema::hasTable('inventory_warehouses')) {
            return null;
        }

        return Warehouse::query()
            ->where('company_id', $companyId)
            ->where('enabled', true)
            ->orderBy('id')
            ->first();
    }
}
