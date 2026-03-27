<?php

namespace Modules\Roles\Http\Requests;

use App\Abstracts\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'template' => ['nullable', Rule::in(['manager', 'accountant', 'employee', 'customer', 'custom'])],
            'permissions' => 'nullable|array',
            'permissions.*.can_view' => 'nullable|boolean',
            'permissions.*.can_create' => 'nullable|boolean',
            'permissions.*.can_edit' => 'nullable|boolean',
            'permissions.*.can_delete' => 'nullable|boolean',
        ];
    }
}
