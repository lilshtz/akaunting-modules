<?php

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosSetting;

class PosOrderService
{
    public function __construct(
        protected PosInventoryService $inventory,
        protected PosInvoiceService $invoices
    ) {
    }

    public function settings(?int $companyId = null): PosSetting
    {
        $companyId ??= company_id();

        return PosSetting::firstOrCreate([
            'company_id' => $companyId,
        ], [
            'receipt_width' => 80,
            'default_payment_method' => 'cash',
            'auto_create_invoice' => false,
            'next_order_number' => 1,
        ]);
    }

    public function create(array $payload): PosOrder
    {
        return DB::transaction(function () use ($payload) {
            $setting = $this->settings();
            $items = $this->normalizeItems($payload['items_json'] ?? '[]');
            $totals = $this->calculateTotals($items);
            $paidAmount = round((float) ($payload['paid_amount'] ?? 0), 4);

            if ($totals['total'] <= 0) {
                throw ValidationException::withMessages([
                    'items_json' => trans('pos::general.messages.empty_order'),
                ]);
            }

            if ($paidAmount + 0.0001 < $totals['total']) {
                throw ValidationException::withMessages([
                    'paid_amount' => trans('pos::general.messages.insufficient_payment'),
                ]);
            }

            $orderNumber = $this->nextOrderNumber($setting);
            $order = PosOrder::create([
                'company_id' => company_id(),
                'contact_id' => $payload['contact_id'] ?: null,
                'order_number' => $orderNumber,
                'status' => PosOrder::STATUS_COMPLETED,
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'discount' => $totals['discount'],
                'total' => $totals['total'],
                'payment_method' => $payload['payment_method'],
                'paid_amount' => $paidAmount,
                'change_amount' => max(0, round($paidAmount - $totals['total'], 4)),
                'tab_name' => $payload['tab_name'] ?: null,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $order->load(['items', 'contact']);

            $this->inventory->deduct($order);
            $this->invoices->createFromOrder($order, $setting);

            return $order;
        });
    }

    public function refund(PosOrder $order, array $refundItems): PosOrder
    {
        return DB::transaction(function () use ($order, $refundItems) {
            $refundLines = [];

            foreach ($refundItems as $payload) {
                $orderItem = $order->items->firstWhere('id', (int) $payload['order_item_id']);

                if (! $orderItem) {
                    continue;
                }

                $quantity = min((float) $payload['quantity'], (float) $orderItem->quantity);

                if ($quantity <= 0) {
                    continue;
                }

                $factor = $quantity / max((float) $orderItem->quantity, 0.0001);
                $refundLines[] = [
                    'item_id' => $orderItem->item_id,
                    'name' => $orderItem->name,
                    'sku' => $orderItem->sku,
                    'quantity' => -1 * $quantity,
                    'price' => $orderItem->price,
                    'discount' => round((float) $orderItem->discount * $factor, 4),
                    'tax' => round((float) $orderItem->tax * $factor, 4),
                    'total' => -1 * round((abs((float) $orderItem->total) * $factor), 4),
                ];
            }

            if (empty($refundLines)) {
                throw ValidationException::withMessages([
                    'items' => trans('pos::general.messages.no_refund_items'),
                ]);
            }

            $setting = $this->settings();
            $totals = $this->calculatePreparedTotals($refundLines);
            $refundOrder = PosOrder::create([
                'company_id' => $order->company_id,
                'contact_id' => $order->contact_id,
                'order_number' => $this->nextOrderNumber($setting),
                'status' => PosOrder::STATUS_REFUNDED,
                'subtotal' => -1 * $totals['subtotal'],
                'tax' => -1 * $totals['tax'],
                'discount' => -1 * $totals['discount'],
                'total' => -1 * $totals['total'],
                'payment_method' => $order->payment_method,
                'paid_amount' => -1 * $totals['total'],
                'change_amount' => 0,
                'tab_name' => trans('pos::general.refund_for', ['order' => $order->order_number]),
            ]);

            foreach ($refundLines as $line) {
                $refundOrder->items()->create($line);
            }

            $refundOrder->load('items');
            $this->inventory->restore($refundOrder);
            $order->update(['status' => PosOrder::STATUS_REFUNDED]);

            return $refundOrder;
        });
    }

    public function normalizeItems(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'items_json' => trans('pos::general.messages.invalid_items'),
            ]);
        }

        $items = [];

        foreach ($decoded as $row) {
            $quantity = max(0, (float) ($row['quantity'] ?? 0));
            $price = max(0, (float) ($row['price'] ?? 0));

            if ($quantity <= 0 || empty($row['name'])) {
                continue;
            }

            $discountPerUnit = max(0, (float) ($row['discount'] ?? 0));
            $taxPerUnit = max(0, (float) ($row['tax'] ?? 0));
            $lineSubtotal = $quantity * $price;
            $lineDiscount = $quantity * $discountPerUnit;
            $lineTax = $quantity * $taxPerUnit;

            $items[] = [
                'item_id' => ! empty($row['item_id']) ? (int) $row['item_id'] : null,
                'name' => (string) $row['name'],
                'sku' => $row['sku'] ?? null,
                'quantity' => $quantity,
                'price' => round($price, 4),
                'discount' => round($lineDiscount, 4),
                'tax' => round($lineTax, 4),
                'total' => round($lineSubtotal - $lineDiscount + $lineTax, 4),
            ];
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items_json' => trans('pos::general.messages.empty_order'),
            ]);
        }

        return $items;
    }

    public function calculateTotals(array $items): array
    {
        return $this->calculatePreparedTotals($items);
    }

    protected function calculatePreparedTotals(array $items): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        $total = 0;

        foreach ($items as $item) {
            $quantity = abs((float) $item['quantity']);
            $price = abs((float) $item['price']);
            $subtotal += $quantity * $price;
            $discount += abs((float) $item['discount']);
            $tax += abs((float) $item['tax']);
            $total += abs((float) $item['total']);
        }

        return [
            'subtotal' => round($subtotal, 4),
            'discount' => round($discount, 4),
            'tax' => round($tax, 4),
            'total' => round($total, 4),
        ];
    }

    protected function nextOrderNumber(PosSetting $setting): string
    {
        $next = (int) $setting->next_order_number;
        $setting->update([
            'next_order_number' => $next + 1,
        ]);

        return 'POS-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
