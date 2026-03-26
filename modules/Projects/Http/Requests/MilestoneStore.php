<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class MilestoneStore extends FormRequest
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
            'target_date' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'position' => 'nullable|integer|min:0',
        ];
    }
}
