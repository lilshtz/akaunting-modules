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
        $accountExists = Rule::exists('double_entry_accounts', 'id')
            ->where(fn ($query) => $query->where('company_id', company_id())->whereNull('deleted_at'));

        return [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'basis' => ['required', Rule::in(['accrual', 'cash'])],
            'status' => ['required', Rule::in(['draft', 'posted'])],
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', $accountExists],
            'lines.*.debit' => 'nullable|required_without:lines.*.credit|numeric|min:0',
            'lines.*.credit' => 'nullable|required_without:lines.*.debit|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->map(function ($line) {
                $line = is_array($line) ? $line : [];

                return [
                    'account_id' => $line['account_id'] ?? null,
                    'debit' => $line['debit'] === '' ? null : ($line['debit'] ?? null),
                    'credit' => $line['credit'] === '' ? null : ($line['credit'] ?? null),
                    'description' => $line['description'] ?? null,
                ];
            })
            ->filter(function (array $line): bool {
                return filled($line['account_id'])
                    || filled($line['debit'])
                    || filled($line['credit'])
                    || filled($line['description']);
            })
            ->values()
            ->all();

        $this->merge([
            'status' => $this->input('status', 'draft'),
            'lines' => $lines,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $debitTotal = 0.0;
            $creditTotal = 0.0;

            foreach ($this->input('lines', []) as $index => $line) {
                $debit = round((float) ($line['debit'] ?? 0), 4);
                $credit = round((float) ($line['credit'] ?? 0), 4);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.$index.debit", trans('double-entry::general.messages.journal_line_single_side'));
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("lines.$index.debit", trans('double-entry::general.messages.journal_line_amount_required'));
                }

                $debitTotal += $debit;
                $creditTotal += $credit;
            }

            if (round($debitTotal, 4) !== round($creditTotal, 4)) {
                $validator->errors()->add('lines', trans('double-entry::general.messages.journal_not_balanced'));
            }
        });
    }
}
