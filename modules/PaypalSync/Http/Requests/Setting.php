<?php

namespace Modules\PaypalSync\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class Setting extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'mode' => 'required|in:sandbox,live',
            'bank_account_id' => 'required|integer|exists:accounts,id',
            'enabled' => 'boolean',
        ];
    }
}
