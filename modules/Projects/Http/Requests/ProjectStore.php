<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ProjectStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,on_hold,cancelled',
            'billing_type' => 'required|in:project_hours,task_hours,fixed_rate',
            'billing_rate' => 'nullable|numeric|min:0',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
            'member_roles' => 'nullable|array',
        ];
    }
}
