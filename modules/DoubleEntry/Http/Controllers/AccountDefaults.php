<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountDefault;

class AccountDefaults extends Controller
{
    protected array $defaultTypes = [
        'accounts_receivable' => ['label' => 'Accounts Receivable', 'type' => 'asset'],
        'accounts_payable' => ['label' => 'Accounts Payable', 'type' => 'liability'],
        'sales_revenue' => ['label' => 'Sales Revenue', 'type' => 'income'],
        'cost_of_goods_sold' => ['label' => 'Cost of Goods Sold', 'type' => 'expense'],
        'bank_checking' => ['label' => 'Bank/Checking', 'type' => 'asset'],
        'owners_equity' => ['label' => "Owner's Equity", 'type' => 'equity'],
    ];

    public function index()
    {
        $accountsByType = Account::query()
            ->byCompany()
            ->where('enabled', true)
            ->orderBy('code')
            ->get()
            ->groupBy('type')
            ->map(function ($accounts) {
                return $accounts->mapWithKeys(fn (Account $account) => [
                    $account->id => $account->code . ' - ' . $account->name,
                ])->all();
            })
            ->all();

        $defaults = AccountDefault::query()
            ->byCompany()
            ->pluck('account_id', 'type')
            ->all();

        return view('double-entry::account-defaults.index', [
            'defaultTypes' => $this->defaultTypes,
            'accountsByType' => $accountsByType,
            'defaults' => $defaults,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (array_keys($this->defaultTypes) as $defaultType) {
            $rules[$defaultType] = [
                'nullable',
                'integer',
                Rule::exists('double_entry_accounts', 'id')->where(
                    fn ($query) => $query->where('company_id', company_id())->whereNull('deleted_at')
                ),
            ];
        }

        $validated = $request->validate($rules);

        foreach ($this->defaultTypes as $defaultType => $config) {
            $accountId = isset($validated[$defaultType]) ? (int) $validated[$defaultType] : null;

            if (! $accountId) {
                AccountDefault::query()
                    ->byCompany()
                    ->where('type', $defaultType)
                    ->delete();

                continue;
            }

            $account = Account::query()
                ->byCompany()
                ->where('type', $config['type'])
                ->findOrFail($accountId);

            AccountDefault::query()->updateOrCreate(
                [
                    'company_id' => company_id(),
                    'type' => $defaultType,
                ],
                [
                    'account_id' => $account->id,
                ]
            );
        }

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.account_defaults')]))->success();

        return redirect()->route('double-entry.account-defaults.index');
    }
}
