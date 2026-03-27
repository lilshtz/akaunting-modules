<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountUpdate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $accountId = $this->route('account');

        return [
            'type' => 'required|in:asset,liability,equity,income,expense',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('double_entry_accounts', 'code')
                    ->where('company_id', company_id())
                    ->ignore($accountId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'integer',
                'different:account',
                Rule::exists('double_entry_accounts', 'id')->where('company_id', company_id()),
            ],
            'opening_balance' => 'nullable|numeric',
            'enabled' => 'nullable|boolean',
        ];
    }
}
