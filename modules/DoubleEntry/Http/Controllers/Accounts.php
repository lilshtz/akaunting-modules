<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Database\Seeds\DefaultAccounts;
use Modules\DoubleEntry\Http\Requests\AccountStore;
use Modules\DoubleEntry\Http\Requests\AccountUpdate;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class Accounts extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $accounts = $this->service->buildAccountHierarchy()
            ->when($request->filled('type'), fn ($collection) => $collection->where('type', $request->get('type')))
            ->groupBy('type');

        return view('double-entry::accounts.index', [
            'accounts' => $accounts,
            'types' => $this->types(),
        ]);
    }

    public function create()
    {
        return view('double-entry::accounts.create', [
            'types' => $this->types(),
            'parents' => $this->service->accountOptions(),
        ]);
    }

    public function store(AccountStore $request): RedirectResponse
    {
        Account::create([
            'company_id' => company_id(),
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'detail_type' => $request->get('detail_type'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->boolean('enabled', true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        flash(trans('messages.success.added', ['type' => trans('double-entry::general.account')]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function edit($account)
    {
        $account = $this->account($account);

        return view('double-entry::accounts.edit', [
            'account' => $account,
            'types' => $this->types(),
            'parents' => array_diff_key($this->service->accountOptions(), [$account->id => true]),
        ]);
    }

    public function update(AccountUpdate $request, $account): RedirectResponse
    {
        $account = $this->account($account);

        $account->update([
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'detail_type' => $request->get('detail_type'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->boolean('enabled'),
            'updated_by' => auth()->id(),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.account')]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function destroy($account): RedirectResponse
    {
        $account = $this->account($account);

        if ($account->children()->exists() || $account->journalLines()->exists()) {
            flash('This account cannot be deleted while it still has child accounts or journal lines.')->warning();

            return redirect()->route('double-entry.accounts.index');
        }

        $account->delete();

        flash(trans('messages.success.deleted', ['type' => trans('double-entry::general.account')]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function import()
    {
        return view('double-entry::accounts.import');
    }

    public function storeImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $rows = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        $header = collect(array_shift($rows))->map(fn ($value) => strtolower(trim((string) $value)));

        foreach ($rows as $row) {
            $data = $header->combine($row);

            if (!$data || empty($data['name'])) {
                continue;
            }

            $type = strtolower((string) ($data['type'] ?? $data['account type'] ?? 'expense'));
            $code = preg_replace('/\D/', '', (string) ($data['code'] ?? $data['number'] ?? ''));

            Account::updateOrCreate(
                [
                    'company_id' => company_id(),
                    'code' => $code ?: (string) random_int(1000, 5999),
                ],
                [
                    'name' => $data['name'] ?? $data['account'] ?? 'Imported Account',
                    'type' => in_array($type, array_keys($this->types()), true) ? $type : Account::TYPE_EXPENSE,
                    'detail_type' => $data['detail type'] ?? null,
                    'description' => $data['description'] ?? null,
                    'opening_balance' => (float) ($data['opening balance'] ?? $data['balance'] ?? 0),
                    'enabled' => true,
                    'updated_by' => auth()->id(),
                    'created_by' => auth()->id(),
                ]
            );
        }

        flash(trans('double-entry::general.csv_imported'))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function seed(): RedirectResponse
    {
        app(DefaultAccounts::class)->run();

        flash(trans('double-entry::general.default_accounts_seeded'))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    protected function account($account): Account
    {
        return Account::where('company_id', company_id())->findOrFail($account);
    }

    protected function types(): array
    {
        return collect(Account::TYPES)->mapWithKeys(fn ($type) => [$type => trans('double-entry::general.types.' . $type)])->all();
    }
}
