<?php

namespace Modules\Roles\Http\Requests;

use App\Abstracts\Http\FormRequest;

class RoleAssignmentStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }
}
