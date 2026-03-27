<?php

namespace Modules\Pos\Services;

use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosSetting;

class PosReceiptService
{
    public function receiptData(PosOrder $order, PosSetting $setting): array
    {
        return [
            'company_name' => company()?->name ?? config('app.name'),
            'order' => $order,
            'setting' => $setting,
            'lines' => $order->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ];
            }),
        ];
    }
}
