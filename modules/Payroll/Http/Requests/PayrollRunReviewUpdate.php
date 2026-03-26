<?php

namespace Modules\Payroll\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class PayrollRunReviewUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lines' => 'required|array|min:1',
            'lines.*.gross_amount' => 'required|numeric|min:0',
            'lines.*.benefit_amount' => 'nullable|numeric|min:0',
            'lines.*.deduction_amount' => 'nullable|numeric|min:0',
            'lines.*.notes' => 'nullable|string',
        ];
    }
}
