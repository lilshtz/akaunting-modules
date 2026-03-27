<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\DoubleEntry\Models\Account;

class AccountUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('account');
        $accountId = is_object($accountId) ? $accountId->id : $accountId;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^(1|2|3|4|5)[0-9]{3,}$/',
                Rule::unique('double_entry_accounts', 'code')
                    ->ignore($accountId)
                    ->where(fn ($query) => $query->where('company_id', company_id())),
            ],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(Account::TYPES)],
            'detail_type' => 'nullable|string|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                'not_in:' . $accountId,
                Rule::exists('double_entry_accounts', 'id')->where(fn ($query) => $query->where('company_id', company_id())),
            ],
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'enabled' => 'nullable|boolean',
        ];
    }
}
