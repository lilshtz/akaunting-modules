<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class DealStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crm_contact_id' => 'required|integer|exists:crm_contacts,id',
            'name' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'stage_id' => 'required|integer|exists:crm_pipeline_stages,id',
            'expected_close' => 'nullable|date',
            'invoice_id' => 'nullable|integer|exists:documents,id',
            'notes' => 'nullable|string',
        ];
    }
}
