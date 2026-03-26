<?php

namespace Modules\Payroll\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class PayCalendarStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:weekly,biweekly,monthly,custom',
            'start_date' => 'required|date',
            'next_run_date' => 'required|date|after_or_equal:start_date',
            'enabled' => 'nullable|boolean',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ];
    }
}
