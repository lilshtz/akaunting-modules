<?php

namespace Modules\Appointments\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class AppointmentFormStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'fields_json' => 'nullable|string',
            'enabled' => 'nullable|boolean',
        ];
    }
}
