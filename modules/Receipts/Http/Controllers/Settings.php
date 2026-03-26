<?php

namespace Modules\Receipts\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\Receipts\Models\CategorizationRule;

class Settings extends Controller
{
    public function index()
    {
        $ocrProvider = setting('receipts.ocr_provider', 'tesseract');
        $ocrApiKey = setting('receipts.ocr_api_key');

        $rules = CategorizationRule::where('company_id', company_id())
            ->orderBy('priority', 'desc')
            ->get();

        $categories = \App\Models\Setting\Category::where('company_id', company_id())
            ->where('type', 'expense')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $accounts = \App\Models\Banking\Account::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('receipts::settings.index', compact(
            'ocrProvider',
            'ocrApiKey',
            'rules',
            'categories',
            'accounts'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'ocr_provider' => 'required|in:tesseract,taggun,mindee',
            'ocr_api_key' => 'nullable|string|max:255',
        ]);

        setting(['receipts.ocr_provider' => $request->get('ocr_provider')]);

        if ($request->filled('ocr_api_key')) {
            setting(['receipts.ocr_api_key' => encrypt($request->get('ocr_api_key'))]);
        }

        setting()->save();

        flash(trans('messages.success.updated', ['type' => trans('receipts::general.settings')]))->success();

        return redirect()->route('receipts.settings.index');
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'vendor_pattern' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'priority' => 'nullable|integer|min:0',
        ]);

        CategorizationRule::create([
            'company_id' => company_id(),
            'vendor_pattern' => $request->get('vendor_pattern'),
            'category_id' => $request->get('category_id'),
            'account_id' => $request->get('account_id'),
            'enabled' => true,
            'priority' => $request->get('priority', 0),
        ]);

        flash(trans('messages.success.added', ['type' => trans('receipts::general.categorization_rule')]))->success();

        return redirect()->route('receipts.settings.index');
    }

    public function destroyRule(int $id)
    {
        $rule = CategorizationRule::where('company_id', company_id())->findOrFail($id);
        $rule->delete();

        flash(trans('messages.success.deleted', ['type' => trans('receipts::general.categorization_rule')]))->success();

        return redirect()->route('receipts.settings.index');
    }
}
