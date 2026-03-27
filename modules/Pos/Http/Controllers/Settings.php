<?php

namespace Modules\Pos\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Pos\Http\Requests\SettingsUpdate;
use Modules\Pos\Services\PosOrderService;

class Settings extends Controller
{
    public function __construct(protected PosOrderService $orders)
    {
    }

    public function edit(): Response
    {
        $setting = $this->orders->settings();

        return view('pos::settings.index', compact('setting'));
    }

    public function update(SettingsUpdate $request): Response
    {
        $setting = $this->orders->settings();
        $setting->update([
            'receipt_width' => $request->integer('receipt_width'),
            'default_payment_method' => $request->get('default_payment_method'),
            'auto_create_invoice' => (bool) $request->boolean('auto_create_invoice'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('pos::general.settings')]))->success();

        return redirect()->route('pos.settings.edit');
    }
}
