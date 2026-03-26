<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class PipelineStageReorder extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stages' => 'required|array|min:1',
            'stages.*' => 'integer|exists:crm_pipeline_stages,id',
        ];
    }
}
