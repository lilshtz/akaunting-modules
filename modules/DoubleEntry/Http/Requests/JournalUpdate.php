<?php

namespace Modules\DoubleEntry\Http\Requests;

use App\Abstracts\Http\FormRequest;

class JournalUpdate extends FormRequest
{
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
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
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
        });
    }
}
