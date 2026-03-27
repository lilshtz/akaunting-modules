<?php

namespace Modules\Appointments\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;
use Modules\Appointments\Models\LeaveRequest;

class SettingsUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (LeaveRequest::TYPES as $type) {
            $rules['leave_types.' . $type] = 'required|string|max:255';
            $rules['leave_allowances.' . $type] = 'required|numeric|min:0';
        }

        return $rules;
    }
}
