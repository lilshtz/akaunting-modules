<?php

namespace Modules\Projects\Http\Requests;

use App\Abstracts\Http\FormRequest;

class TransactionStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => 'required|in:invoice,bill',
            'document_id' => 'required|integer|exists:documents,id',
        ];
    }
}
