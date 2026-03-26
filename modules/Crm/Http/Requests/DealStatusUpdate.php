<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class DealStatusUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:open,won,lost',
            'stage_id' => 'nullable|integer|exists:crm_pipeline_stages,id',
        ];
    }
}
