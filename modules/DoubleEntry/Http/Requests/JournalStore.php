<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|string|max:20',
            'next_run_at' => 'nullable|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => [
                'required',
                'integer',
                Rule::exists('double_entry_accounts', 'id')->where(fn ($query) => $query->where('company_id', company_id())),
            ],
            'lines.*.entry_type' => 'required|in:debit,credit',
            'lines.*.description' => 'nullable|string',
            'lines.*.amount' => 'required|numeric|min:0.0001',
        ];
    }
}
