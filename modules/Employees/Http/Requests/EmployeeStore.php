<?php

namespace Modules\Employees\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class EmployeeStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'required_without:contact_name|nullable|integer|exists:contacts,id',
            'contact_name' => 'required_without:contact_id|nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'photo' => 'nullable|image|max:2048',
            'hire_date' => 'nullable|date',
            'birthday' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:hourly,weekly,biweekly,monthly,yearly',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'bank_routing' => 'nullable|string|max:255',
            'type' => 'required|in:full_time,part_time,contractor,seasonal',
            'classification' => 'required|in:w2,1099',
            'status' => 'nullable|in:active,inactive,terminated',
            'notes' => 'nullable|string',
        ];
    }
}
