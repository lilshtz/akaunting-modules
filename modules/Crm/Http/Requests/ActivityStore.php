<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ActivityStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:call,meeting,email,note,task',
            'description' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'completed_at' => 'nullable|date|after_or_equal:scheduled_at',
        ];
    }
}
