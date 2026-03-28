<?php

namespace Modules\DoubleEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\DoubleEntry\Models\Account;

class AccountUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = (int) $this->route('account');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('double_entry_accounts', 'code')
                    ->ignore($accountId)
                    ->where(fn ($query) => $query->where('company_id', company_id())->whereNull('deleted_at')),
            ],
            'name' => 'required|string|max:191',
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'parent_id' => [
                'nullable',
                Rule::exists('double_entry_accounts', 'id')
                    ->where(fn ($query) => $query->where('company_id', company_id())->whereNull('deleted_at')),
            ],
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'enabled' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $accountId = (int) $this->route('account');
            $parentId = $this->integer('parent_id');

            if (! $parentId) {
                return;
            }

            if ($parentId === $accountId) {
                $validator->errors()->add('parent_id', trans('double-entry::general.messages.parent_self'));

                return;
            }

            $parent = Account::query()->byCompany()->find($parentId);

            if (! $parent) {
                return;
            }

            if ($parent->type !== $this->input('type')) {
                $validator->errors()->add('parent_id', trans('double-entry::general.messages.parent_type_mismatch'));
            }

            $ancestorId = $parent->parent_id;

            while ($ancestorId) {
                if ((int) $ancestorId === $accountId) {
                    $validator->errors()->add('parent_id', trans('double-entry::general.messages.parent_cycle'));

                    return;
                }

                $ancestorId = Account::query()->byCompany()->whereKey($ancestorId)->value('parent_id');
            }
        });
    }
}
