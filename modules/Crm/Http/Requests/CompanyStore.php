<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class CompanyStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'default_stage' => 'required|in:lead,subscriber,opportunity,customer',
        ];
    }
}
