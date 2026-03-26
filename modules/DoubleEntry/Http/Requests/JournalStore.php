<?php

namespace Modules\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\DoubleEntry\Models\Account;

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
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'basis' => 'required|in:accrual,cash',
            'status' => 'required|in:draft,posted',
            'recurring_frequency' => 'nullable|in:weekly,monthly,quarterly,yearly',
            'next_recurring_date' => 'nullable|date|required_with:recurring_frequency',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->get('lines', []);

            if (count($lines) < 2) {
                return;
            }

            $totalDebit = 0;
            $totalCredit = 0;
            $companyId = company_id();

            foreach ($lines as $i => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.{$i}", trans('double-entry::general.validation.debit_or_credit'));
                }

                if ($debit == 0 && $credit == 0) {
                    $validator->errors()->add("lines.{$i}", trans('double-entry::general.validation.line_amount_required'));
                }

                if (! empty($line['account_id'])) {
                    $account = Account::where('company_id', $companyId)->find($line['account_id']);
                    if (! $account) {
                        $validator->errors()->add("lines.{$i}.account_id", trans('double-entry::general.validation.account_not_found'));
                    }
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 4) !== round($totalCredit, 4)) {
                $validator->errors()->add('lines', trans('double-entry::general.validation.unbalanced', [
                    'debit' => number_format($totalDebit, 2),
                    'credit' => number_format($totalCredit, 2),
                ]));
            }
        });
    }
}
