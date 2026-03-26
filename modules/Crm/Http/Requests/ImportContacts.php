<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ImportContacts extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt',
            'source' => 'nullable|in:web,referral,email,cold,phone,other',
            'stage' => 'nullable|in:lead,subscriber,opportunity,customer',
            'crm_company_id' => 'nullable|integer|exists:crm_companies,id',
            'owner_user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
