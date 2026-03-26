<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class PipelineStageStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_won' => 'nullable|boolean',
            'is_lost' => 'nullable|boolean',
        ];
    }
}
