<?php

namespace Modules\Employees\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class DepartmentUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|integer|exists:employees,id',
        ];
    }
}
