<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as BaseRequest;
use Modules\DoubleEntry\Http\Requests\AccountStore;
use Modules\DoubleEntry\Http\Requests\AccountUpdate;
use Modules\DoubleEntry\Models\Account;

class Accounts extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::where('company_id', company_id())
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        return view('double-entry::accounts.index', compact('accounts', 'types'));
    }

    public function create()
    {
        return view('double-entry::accounts.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountStore $request)
    {
        $account = Account::create([
            'company_id' => company_id(),
            'parent_id' => $request->get('parent_id') ?: null,
            'type' => $request->get('type'),
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.added', ['type' => $account->name]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.accounts.index'),
        ]);
    }

    public function edit(int $id)
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        return view('double-entry::accounts.edit', array_merge(
            ['account' => $account],
            $this->formData($id)
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id, AccountUpdate $request)
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        $account->update([
            'parent_id' => $request->get('parent_id') ?: null,
            'type' => $request->get('type'),
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.updated', ['type' => $account->name]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.accounts.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        if ($account->journalLines()->exists()) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => trans('double-entry::general.error.has_transactions'),
            ]);
        }

        $account->delete();

        $message = trans('messages.success.deleted', ['type' => $account->name]);

        flash($message)->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'message' => $message,
            'redirect' => route('double-entry.accounts.index'),
        ]);
    }

    public function import()
    {
        return view('double-entry::accounts.import');
    }

    public function storeImport(BaseRequest $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $data = array_combine(array_map(static fn ($value) => strtolower(trim($value)), $header), $row);
            $code = trim((string) ($data['code'] ?? $data['account code'] ?? ''));
            $name = trim((string) ($data['name'] ?? $data['account name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            Account::firstOrCreate(
                [
                    'company_id' => company_id(),
                    'code' => $code,
                ],
                [
                    'company_id' => company_id(),
                    'type' => strtolower($data['type'] ?? $data['account type'] ?? 'asset'),
                    'code' => $code,
                    'name' => $name,
                    'description' => $data['description'] ?? '',
                    'opening_balance' => (float) ($data['opening_balance'] ?? $data['balance'] ?? 0),
                    'enabled' => true,
                ]
            );

            $imported++;
        }

        fclose($handle);

        $message = trans('double-entry::general.imported', ['count' => $imported]);

        flash($message)->success();

        return redirect()->route('double-entry.accounts.index');
    }

    protected function formData(?int $exceptId = null): array
    {
        $query = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code');

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return [
            'types' => collect(trans('double-entry::general.types'))->toArray(),
            'parentAccounts' => $query->get()->mapWithKeys(function (Account $account) {
                return [$account->id => $account->code . ' - ' . $account->name];
            })->prepend(trans('general.none'), '')->toArray(),
        ];
    }
}
