<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\DoubleEntry\Http\Requests\AccountStore;
use Modules\DoubleEntry\Http\Requests\AccountUpdate;
use Modules\DoubleEntry\Models\Account;

class Accounts extends Controller
{
    public function index(): Response|mixed
    {
        $accounts = Account::collect([
            'company_id' => company_id(),
        ]);

        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        $accountsByType = [];
        foreach ($types as $type) {
            $accountsByType[$type] = Account::where('company_id', company_id())
                ->where('type', $type)
                ->orderBy('code')
                ->get();
        }

        return $this->response('double-entry::accounts.index', compact('accounts', 'accountsByType', 'types'));
    }

    public function create(): Response|mixed
    {
        $types = [
            'asset' => trans('double-entry::general.types.asset'),
            'liability' => trans('double-entry::general.types.liability'),
            'equity' => trans('double-entry::general.types.equity'),
            'income' => trans('double-entry::general.types.income'),
            'expense' => trans('double-entry::general.types.expense'),
        ];

        $parents = Account::where('company_id', company_id())
            ->whereNull('parent_id')
            ->enabled()
            ->orderBy('code')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        return view('double-entry::accounts.create', compact('types', 'parents'));
    }

    public function store(AccountStore $request): Response|mixed
    {
        $account = Account::create([
            'company_id' => company_id(),
            'parent_id' => $request->get('parent_id') ?: null,
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.added', ['type' => $account->name]);

        flash($message)->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function edit(int $id): Response|mixed
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        $types = [
            'asset' => trans('double-entry::general.types.asset'),
            'liability' => trans('double-entry::general.types.liability'),
            'equity' => trans('double-entry::general.types.equity'),
            'income' => trans('double-entry::general.types.income'),
            'expense' => trans('double-entry::general.types.expense'),
        ];

        $parents = Account::where('company_id', company_id())
            ->whereNull('parent_id')
            ->where('id', '!=', $id)
            ->enabled()
            ->orderBy('code')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        return view('double-entry::accounts.edit', compact('account', 'types', 'parents'));
    }

    public function update(int $id, AccountUpdate $request): Response|mixed
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        $account->update([
            'parent_id' => $request->get('parent_id') ?: null,
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'description' => $request->get('description'),
            'opening_balance' => $request->get('opening_balance', 0),
            'enabled' => $request->get('enabled', true),
        ]);

        $message = trans('messages.success.updated', ['type' => $account->name]);

        flash($message)->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function destroy(int $id): Response|mixed
    {
        $account = Account::where('company_id', company_id())->findOrFail($id);

        $account->delete();

        $message = trans('messages.success.deleted', ['type' => $account->name]);

        flash($message)->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function import(): Response|mixed
    {
        return view('double-entry::accounts.import');
    }

    public function importProcess(Request $request): Response|mixed
    {
        $request->validate([
            'import' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('import');
        $handle = fopen($file->getPathname(), 'r');

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $code = trim($row[0]);
            $name = trim($row[1]);
            $type = strtolower(trim($row[2]));
            $parentCode = isset($row[3]) ? trim($row[3]) : null;
            $description = isset($row[4]) ? trim($row[4]) : null;
            $openingBalance = isset($row[5]) ? (float) trim($row[5]) : 0;

            if (! in_array($type, ['asset', 'liability', 'equity', 'income', 'expense'])) {
                continue;
            }

            $parentId = null;
            if ($parentCode) {
                $parent = Account::where('company_id', company_id())
                    ->where('code', $parentCode)
                    ->first();
                $parentId = $parent?->id;
            }

            Account::firstOrCreate(
                [
                    'company_id' => company_id(),
                    'code' => $code,
                ],
                [
                    'company_id' => company_id(),
                    'name' => $name,
                    'type' => $type,
                    'parent_id' => $parentId,
                    'description' => $description,
                    'opening_balance' => $openingBalance,
                    'enabled' => true,
                ]
            );

            $imported++;
        }

        fclose($handle);

        $message = trans('messages.success.imported', ['type' => trans_choice('double-entry::general.accounts', 2)]);

        flash($message)->success();

        return redirect()->route('double-entry.accounts.index');
    }
}
