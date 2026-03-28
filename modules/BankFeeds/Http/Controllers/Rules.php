<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\BankFeeds\Http\Requests\RuleStore;
use Modules\BankFeeds\Models\Rule;
use Modules\DoubleEntry\Models\Account;

class Rules extends Controller
{
    public function index()
    {
        $rules = Rule::query()
            ->byCompany()
            ->with('category')
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate(25);

        return view('bank-feeds::rules.index', compact('rules'));
    }

    public function create()
    {
        return view('bank-feeds::rules.create', $this->formData());
    }

    public function store(RuleStore $request): RedirectResponse
    {
        Rule::create([
            'company_id' => company_id(),
            'name' => $request->string('name')->toString(),
            'field' => $request->string('field')->toString(),
            'operator' => $request->string('operator')->toString(),
            'value' => $request->string('value')->toString(),
            'value_end' => $request->input('value_end'),
            'category_id' => $request->integer('category_id') ?: null,
            'enabled' => $request->boolean('enabled', false),
            'priority' => $request->integer('priority'),
        ]);

        flash(trans('messages.success.added', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    public function show(int $id): RedirectResponse
    {
        return redirect()->route('bank-feeds.rules.edit', $id);
    }

    public function edit(int $id)
    {
        $rule = Rule::query()->byCompany()->findOrFail($id);

        return view('bank-feeds::rules.edit', array_merge($this->formData(), compact('rule')));
    }

    public function update(RuleStore $request, int $id): RedirectResponse
    {
        $rule = Rule::query()->byCompany()->findOrFail($id);

        $rule->update([
            'name' => $request->string('name')->toString(),
            'field' => $request->string('field')->toString(),
            'operator' => $request->string('operator')->toString(),
            'value' => $request->string('value')->toString(),
            'value_end' => $request->input('value_end'),
            'category_id' => $request->integer('category_id') ?: null,
            'enabled' => $request->boolean('enabled', false),
            'priority' => $request->integer('priority'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $rule = Rule::query()->byCompany()->findOrFail($id);
        $rule->delete();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.rule')]))->success();

        return redirect()->route('bank-feeds.rules.index');
    }

    public function apply(): RedirectResponse
    {
        flash(trans('bank-feeds::general.messages.rules_apply_placeholder'))->warning();

        return redirect()->route('bank-feeds.transactions.index');
    }

    protected function formData(): array
    {
        return [
            'categoryOptions' => Account::query()
                ->byCompany()
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (Account $account) => [$account->id => trim($account->code . ' - ' . $account->name)])
                ->all(),
            'fieldOptions' => [
                'description' => trans('bank-feeds::general.rule_fields.description'),
                'amount' => trans('bank-feeds::general.rule_fields.amount'),
                'type' => trans('bank-feeds::general.rule_fields.type'),
            ],
            'operatorOptions' => [
                'contains' => trans('bank-feeds::general.operators.contains'),
                'equals' => trans('bank-feeds::general.operators.equals'),
                'starts_with' => trans('bank-feeds::general.operators.starts_with'),
                'gt' => trans('bank-feeds::general.operators.gt'),
                'lt' => trans('bank-feeds::general.operators.lt'),
                'between' => trans('bank-feeds::general.operators.between'),
            ],
        ];
    }
}
