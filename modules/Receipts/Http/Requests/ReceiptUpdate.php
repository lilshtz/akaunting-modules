<?php

namespace Modules\Receipts\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ReceiptUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_name' => 'nullable|string|max:255',
            'receipt_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'category_id' => 'nullable|integer|exists:categories,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
