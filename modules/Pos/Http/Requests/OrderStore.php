<?php

namespace Modules\Pos\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class OrderStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'tab_name' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,card,split',
            'paid_amount' => 'required|numeric|min:0',
            'split_count' => 'nullable|integer|min:1|max:20',
            'items_json' => 'required|string',
        ];
    }
}
