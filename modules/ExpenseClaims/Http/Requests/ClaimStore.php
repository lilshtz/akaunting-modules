<?php

namespace Modules\ExpenseClaims\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class ClaimStore extends Request
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
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'nullable|integer|exists:expense_claim_categories,id',
            'items.*.date' => 'required|date',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.paid_by_employee' => 'nullable|boolean',
            'items.*.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
