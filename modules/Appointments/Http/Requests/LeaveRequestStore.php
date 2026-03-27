<?php

namespace Modules\Appointments\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class LeaveRequestStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'approver_id' => 'nullable|integer|exists:users,id',
            'type' => 'required|in:vacation,sick,personal,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ];
    }
}
