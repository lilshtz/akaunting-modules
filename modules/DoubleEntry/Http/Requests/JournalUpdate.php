<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;

class JournalUpdate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'number' => 'required|string|max:50',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,posted',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:weekly,monthly,quarterly,yearly',
            'next_run_at' => 'nullable|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:double_entry_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->get('lines', []);
            $totalDebits = array_sum(array_column($lines, 'debit'));
            $totalCredits = array_sum(array_column($lines, 'credit'));

            if (bccomp($totalDebits, $totalCredits, 4) !== 0) {
                $validator->errors()->add('lines', trans('double-entry::general.error.not_balanced'));
            }

            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if (($debit > 0 && $credit > 0) || ($debit == 0.0 && $credit == 0.0)) {
                    $validator->errors()->add("lines.$index", trans('double-entry::general.error.invalid_line'));
                }
            }
        });
    }
}
