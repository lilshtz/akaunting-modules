<?php

namespace Modules\Stripe\Http\Requests;

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
            'api_key' => 'required|string',
            'webhook_secret' => 'required|string',
            'test_mode' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
