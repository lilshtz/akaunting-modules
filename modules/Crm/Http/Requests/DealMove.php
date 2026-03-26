<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class DealMove extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage_id' => 'required|integer|exists:crm_pipeline_stages,id',
        ];
    }
}
