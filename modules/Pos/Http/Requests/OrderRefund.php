<?php

namespace Modules\Pos\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class OrderRefund extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:pos_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ];
    }
}
