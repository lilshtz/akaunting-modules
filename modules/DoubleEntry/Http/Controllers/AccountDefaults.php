<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Services\AccountBalanceService;

class AccountDefaults extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index()
    {
        return view('double-entry::account-defaults.index', [
            'accounts' => $this->service->accountOptions(),
            'defaults' => $this->service->defaultMappings(),
            'keys' => trans('double-entry::general.defaults'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $keys = array_keys(trans('double-entry::general.defaults'));

        $request->validate([
            'defaults' => 'required|array',
            'defaults.*' => [
                'nullable',
                'integer',
                Rule::exists('double_entry_accounts', 'id')->where(fn ($query) => $query->where('company_id', company_id())),
            ],
        ]);

        foreach ($keys as $key) {
            AccountDefault::updateOrCreate(
                ['company_id' => company_id(), 'key' => $key],
                ['account_id' => $request->input('defaults.' . $key) ?: null]
            );
        }

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.account_defaults')]))->success();

        return redirect()->route('double-entry.account-defaults.index');
    }
}
