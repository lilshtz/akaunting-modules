<?php

namespace Modules\Estimates\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class EstimateStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
            'issued_at' => 'required|date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'currency_code' => 'required|string|size:3',
            'currency_rate' => 'nullable|numeric|gt:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:255',
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
