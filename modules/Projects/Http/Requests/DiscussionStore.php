<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class DiscussionStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:project_discussions,id',
            'body' => 'required|string',
        ];
    }
}
