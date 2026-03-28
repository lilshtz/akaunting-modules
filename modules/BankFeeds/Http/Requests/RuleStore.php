<?php

namespace Modules\BankFeeds\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;
use Illuminate\Validation\Rule as ValidationRule;

class RuleStore extends Request
{
    protected array $operatorsByField = [
        'description' => ['contains', 'equals', 'starts_with'],
        'amount' => ['gt', 'lt', 'between'],
        'type' => ['equals'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'field' => ['required', ValidationRule::in(array_keys($this->operatorsByField))],
            'operator' => ['required', ValidationRule::in(['contains', 'equals', 'starts_with', 'gt', 'lt', 'between'])],
            'value' => 'required|string|max:255',
            'value_end' => 'nullable|string|max:255',
            'category_id' => 'required|integer',
            'enabled' => 'nullable|boolean',
            'priority' => 'required|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $field = (string) $this->input('field');
            $operator = (string) $this->input('operator');

            if ($field && $operator && ! in_array($operator, $this->operatorsByField[$field] ?? [], true)) {
                $validator->errors()->add('operator', trans('bank-feeds::general.messages.invalid_operator'));
            }

            if ($field === 'amount') {
                if (! is_numeric($this->input('value'))) {
                    $validator->errors()->add('value', trans('bank-feeds::general.messages.numeric_rule_value'));
                }

                if ($operator === 'between' && ! is_numeric($this->input('value_end'))) {
                    $validator->errors()->add('value_end', trans('bank-feeds::general.messages.between_value_required'));
                }
            }

            if ($field === 'type' && ! in_array($this->input('value'), ['deposit', 'withdrawal'], true)) {
                $validator->errors()->add('value', trans('bank-feeds::general.messages.invalid_type_value'));
            }

            if ($operator !== 'between' && $this->filled('value_end')) {
                $validator->errors()->add('value_end', trans('bank-feeds::general.messages.value_end_between_only'));
            }
        });
    }
}
