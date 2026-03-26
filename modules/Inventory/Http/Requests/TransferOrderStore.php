<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferOrderStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => 'required|integer|different:to_warehouse_id|exists:inventory_warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:inventory_warehouses,id',
            'status' => 'nullable|string|in:draft,in_transit,received,cancelled',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:items,id',
            'items.*.variant_id' => 'nullable|integer|exists:inventory_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ];
    }
}
