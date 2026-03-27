<?php

namespace Modules\Pos\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class SettingsUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_width' => 'required|integer|min:40|max:120',
            'default_payment_method' => 'required|in:cash,card,split',
            'auto_create_invoice' => 'nullable|boolean',
        ];
    }
}
