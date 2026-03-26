<?php

namespace Modules\CustomFields\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class FieldUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'field_type' => 'required|in:text,textarea,number,date,datetime,time,select,checkbox,toggle,url,email',
            'required' => 'nullable|boolean',
            'default_value' => 'nullable|string',
            'options_text' => 'nullable|string',
            'options_json' => 'nullable|array',
            'options_json.*' => 'string|max:255',
            'position' => 'nullable|integer|min:0',
            'show_on_pdf' => 'nullable|boolean',
            'width' => 'nullable|in:full,half',
            'enabled' => 'nullable|boolean',
        ];
    }
}
