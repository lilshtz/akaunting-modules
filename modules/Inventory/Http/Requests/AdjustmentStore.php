<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|integer|exists:inventory_warehouses,id',
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:inventory_variants,id',
            'quantity' => 'required|numeric|not_in:0',
            'reason' => 'required|string|in:damaged,missing,stolen,returned,recount,other',
            'description' => 'nullable|string|max:1000',
            'date' => 'nullable|date',
        ];
    }
}
