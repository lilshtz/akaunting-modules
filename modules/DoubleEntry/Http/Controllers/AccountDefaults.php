<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountDefault;

class AccountDefaults extends Controller
{
    /**
     * Display account defaults form.
     */
    public function index()
    {
        $types = AccountDefault::getTypes();

        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get();

        $defaults = AccountDefault::where('company_id', company_id())
            ->pluck('account_id', 'type')
            ->toArray();

        return view('double-entry::accounts.defaults', compact('types', 'accounts', 'defaults'));
    }

    /**
     * Update account defaults.
     */
    public function update(Request $request)
    {
        $types = AccountDefault::getTypes();

        foreach (array_keys($types) as $type) {
            $accountId = $request->get($type);

            if ($accountId) {
                AccountDefault::updateOrCreate(
                    [
                        'company_id' => company_id(),
                        'type' => $type,
                    ],
                    [
                        'account_id' => $accountId,
                    ]
                );
            } else {
                AccountDefault::where('company_id', company_id())
                    ->where('type', $type)
                    ->delete();
            }
        }

        $message = trans('messages.success.updated', ['type' => trans('double-entry::general.account_defaults')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.account-defaults.index'),
        ]);
    }
}
