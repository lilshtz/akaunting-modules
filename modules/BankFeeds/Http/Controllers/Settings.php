<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;

class Settings extends Controller
{
    public function index()
    {
        $companyId = company_id();

        // Get all saved column mappings
        $mappings = [];
        $settings = setting()->all();

        foreach ($settings as $key => $value) {
            if (str_starts_with($key, "bank_feeds.mapping.{$companyId}.")) {
                $accountId = str_replace("bank_feeds.mapping.{$companyId}.", '', $key);
                $mappings[$accountId] = json_decode($value, true);
            }
        }

        return view('bank-feeds::settings.index', compact('mappings'));
    }

    public function update(Request $request)
    {
        // Update general bank feed settings
        if ($request->has('default_format')) {
            setting(["bank_feeds.default_format.{company_id()}" => $request->get('default_format')]);
            setting()->save();
        }

        flash(trans('messages.success.updated', ['type' => trans('bank-feeds::general.settings')]))->success();

        return redirect()->route('bank-feeds.settings.index');
    }

    /**
     * Delete a saved column mapping.
     */
    public function deleteMapping(int $accountId)
    {
        $key = "bank_feeds.mapping." . company_id() . ".{$accountId}";
        setting()->forget($key);
        setting()->save();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.column_mapping')]))->success();

        return redirect()->route('bank-feeds.settings.index');
    }
}
