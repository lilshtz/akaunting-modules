<?php

namespace Modules\Appointments\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class AppointmentStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'user_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:scheduled,completed,cancelled,no_show',
            'notes' => 'nullable|string',
        ];
    }
}
