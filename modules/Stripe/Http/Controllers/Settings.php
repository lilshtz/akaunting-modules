<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Stripe\Http\Requests\Setting as Request;
use Modules\Stripe\Models\StripeSettings;

class Settings extends Controller
{
    /**
     * Show the form for editing Stripe settings.
     *
     * @return Response
     */
    public function edit()
    {
        $settings = StripeSettings::where('company_id', company_id())->first();

        if (!$settings) {
            $settings = new StripeSettings([
                'company_id' => company_id(),
                'test_mode' => true,
                'enabled' => false,
            ]);
        }

        $webhookUrl = route('stripe.webhook.handle');

        return view('stripe::settings', compact('settings', 'webhookUrl'));
    }

    /**
     * Update Stripe settings in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $settings = StripeSettings::where('company_id', company_id())->first();

        $data = [
            'company_id' => company_id(),
            'api_key' => $request->get('api_key'),
            'webhook_secret' => $request->get('webhook_secret'),
            'test_mode' => $request->get('test_mode', false),
            'enabled' => $request->get('enabled', false),
        ];

        if ($settings) {
            // Only update api_key if a new value was provided (not masked)
            if ($request->get('api_key') === '••••••••') {
                unset($data['api_key']);
            }
            if ($request->get('webhook_secret') === '••••••••') {
                unset($data['webhook_secret']);
            }

            $settings->update($data);
        } else {
            $settings = StripeSettings::create($data);
        }

        $message = trans('messages.success.updated', ['type' => trans('stripe::general.name')]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('stripe.settings.edit'),
        ]);
    }
}
