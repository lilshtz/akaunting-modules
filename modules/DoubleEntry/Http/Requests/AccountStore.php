<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class AccountStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = company_id();

        return [
            'code' => "required|string|max:50|unique:double_entry_accounts,code,NULL,id,company_id,{$companyId},deleted_at,NULL",
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|integer|exists:double_entry_accounts,id',
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'enabled' => 'nullable|boolean',
        ];
    }
}
