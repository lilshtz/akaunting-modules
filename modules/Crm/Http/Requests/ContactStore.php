<?php

namespace Modules\Crm\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ContactStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crm_company_id' => 'nullable|integer|exists:crm_companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'source' => 'required|in:web,referral,email,cold,phone,other',
            'stage' => 'required|in:lead,subscriber,opportunity,customer',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string',
        ];
    }
}
