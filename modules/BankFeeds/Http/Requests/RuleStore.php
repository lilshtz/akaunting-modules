<?php

namespace Modules\BankFeeds\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;
use Illuminate\Validation\Rule as ValidationRule;

class RuleStore extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'field' => ['required', ValidationRule::in(['description', 'amount', 'type'])],
            'operator' => ['required', ValidationRule::in(['contains', 'equals', 'starts_with', 'gt', 'lt', 'between'])],
            'value' => 'required|string|max:255',
            'value_end' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ];
    }
}
