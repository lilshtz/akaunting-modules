<?php

namespace Modules\PaypalSync\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Banking\Account;
use Illuminate\Http\Response;
use Modules\PaypalSync\Http\Requests\Setting as Request;
use Modules\PaypalSync\Models\PaypalSyncSettings;

class Settings extends Controller
{
    /**
     * Show the form for editing the PayPal Sync settings.
     *
     * @return Response
     */
    public function edit()
    {
        $settings = PaypalSyncSettings::where('company_id', company_id())->first();

        if (!$settings) {
            $settings = new PaypalSyncSettings([
                'company_id' => company_id(),
                'mode' => 'sandbox',
                'enabled' => true,
            ]);
        }

        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->pluck('name', 'id')
            ->toArray();

        return view('paypal-sync::settings', compact('settings', 'accounts'));
    }

    /**
     * Update the PayPal Sync settings.
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $settings = PaypalSyncSettings::where('company_id', company_id())->first();

        $data = [
            'company_id' => company_id(),
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
            'mode' => $request->input('mode'),
            'bank_account_id' => $request->input('bank_account_id'),
            'enabled' => $request->input('enabled', false),
        ];

        if ($settings) {
            $settings->update($data);
        } else {
            $settings = PaypalSyncSettings::create($data);
        }

        $message = trans('messages.success.updated', ['type' => trans('paypal-sync::general.name')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('paypal-sync.settings.edit'),
        ]);
    }
}
