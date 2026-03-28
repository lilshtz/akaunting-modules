<?php

namespace Modules\BankFeeds\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class ImportUpload extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'bank_account_id' => 'nullable|integer',
        ];
    }
}
