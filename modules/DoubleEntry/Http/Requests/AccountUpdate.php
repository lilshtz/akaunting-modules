<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class AccountUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = company_id();
        $id = $this->route('account');

        return [
            'code' => "required|string|max:50|unique:double_entry_accounts,code,{$id},id,company_id,{$companyId},deleted_at,NULL",
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|integer|exists:double_entry_accounts,id',
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'enabled' => 'nullable|boolean',
        ];
    }
}
