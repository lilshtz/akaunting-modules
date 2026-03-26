<?php

namespace Modules\CreditDebitNotes\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class CreditNoteStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'required|integer|exists:documents,id',
            'issued_at' => 'required|date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'currency_code' => 'required|string|size:3',
            'currency_rate' => 'nullable|numeric|gt:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_id' => 'nullable|integer|exists:taxes,id',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
        ];
    }
}
