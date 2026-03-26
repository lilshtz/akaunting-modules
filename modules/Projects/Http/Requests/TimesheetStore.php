<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class TimesheetStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_id' => 'required|integer|exists:project_tasks,id',
            'work_date' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'billable' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];
    }
}
