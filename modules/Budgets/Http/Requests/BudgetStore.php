<?php

namespace Modules\Budgets\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class BudgetStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'period_type' => 'required|in:monthly,quarterly,annual',
            'scenario' => 'nullable|in:optimistic,realistic,pessimistic',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'status' => 'required|in:draft,active,closed',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|integer|exists:double_entry_accounts,id|distinct',
            'lines.*.amount' => 'required|numeric',
        ];
    }
}
