<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class TaskStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'milestone_id' => 'nullable|integer|exists:project_milestones,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|integer|exists:employees,id',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:todo,in_progress,review,done',
            'estimated_hours' => 'nullable|numeric|min:0',
            'position' => 'nullable|integer|min:0',
        ];
    }
}
