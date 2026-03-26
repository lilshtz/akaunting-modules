<?php

namespace Modules\Payroll\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class PayItemStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:benefit,deduction',
            'name' => 'required|string|max:255',
            'default_amount' => 'nullable|numeric|min:0',
            'is_percentage' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
        ];
    }
}
