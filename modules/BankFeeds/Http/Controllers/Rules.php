<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\BankFeeds\Http\Requests\RuleStore;
use Modules\BankFeeds\Http\Requests\RuleUpdate;
use Modules\BankFeeds\Models\BankFeedRule;

class Rules extends Controller
{
    public function index()
    {
        $rules = BankFeedRule::where('company_id', company_id())
            ->ordered()
            ->paginate(50);

        return view('bank-feeds::rules.index', compact('rules'));
    }

    public function create()
    {
        $fields = BankFeedRule::FIELDS;
        $operators = BankFeedRule::OPERATORS;

        return view('bank-feeds::rules.create', compact('fields', 'operators'));
    }

    public function store(RuleStore $request)
    {
        $data = $request->validated();
        $data['company_id'] = company_id();

        BankFeedRule::create($data);

        flash(trans('messages.success.added', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    public function edit(int $id)
    {
        $rule = BankFeedRule::where('company_id', company_id())->findOrFail($id);
        $fields = BankFeedRule::FIELDS;
        $operators = BankFeedRule::OPERATORS;

        return view('bank-feeds::rules.edit', compact('rule', 'fields', 'operators'));
    }

    public function update(RuleUpdate $request, int $id)
    {
        $rule = BankFeedRule::where('company_id', company_id())->findOrFail($id);
        $rule->update($request->validated());

        flash(trans('messages.success.updated', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    public function destroy(int $id)
    {
        $rule = BankFeedRule::where('company_id', company_id())->findOrFail($id);
        $rule->delete();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    /**
     * Reorder rules via AJAX.
     */
    public function reorder(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'rules' => 'required|array',
            'rules.*.id' => 'required|integer',
            'rules.*.priority' => 'required|integer',
        ]);

        foreach ($request->get('rules') as $item) {
            BankFeedRule::where('company_id', company_id())
                ->where('id', $item['id'])
                ->update(['priority' => $item['priority']]);
        }

        return response()->json(['success' => true]);
    }
}
