<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = (int) $this->route('variant');

        return [
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:255', Rule::unique('inventory_variants', 'sku')->ignore($variantId)],
            'attributes_json' => 'nullable|array',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
        ];
    }
}
