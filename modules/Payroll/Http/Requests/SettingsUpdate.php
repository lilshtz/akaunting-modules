<?php

namespace Modules\Payroll\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class SettingsUpdate extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_benefit_items' => 'nullable|array',
            'default_benefit_items.*' => 'integer|exists:pay_items,id',
            'default_deduction_items' => 'nullable|array',
            'default_deduction_items.*' => 'integer|exists:pay_items,id',
            'salary_expense_account_id' => 'nullable|integer|exists:double_entry_accounts,id',
            'bank_account_id' => 'nullable|integer|exists:double_entry_accounts,id',
            'deduction_account_id' => 'nullable|integer|exists:double_entry_accounts,id',
            'hours_per_week' => 'nullable|numeric|min:1',
        ];
    }
}
