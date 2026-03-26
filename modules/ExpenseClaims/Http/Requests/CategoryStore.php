<?php

namespace Modules\ExpenseClaims\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class CategoryStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'enabled' => 'nullable|boolean',
        ];
    }
}
