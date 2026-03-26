<?php

namespace Modules\BankFeeds\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RuleStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field' => 'required|in:description,vendor,amount',
            'operator' => 'required|in:contains,equals,starts_with,gt,lt,between',
            'value' => 'required|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'vendor_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ];
    }
}
