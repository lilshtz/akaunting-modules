<?php

namespace Modules\Receipts\Http\Requests;

use App\Abstracts\Http\FormRequest;

class ReceiptStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,bmp,tiff,webp|max:10240',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
