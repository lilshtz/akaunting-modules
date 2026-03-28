<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Http\Requests\AccountStore;
use Modules\DoubleEntry\Http\Requests\AccountUpdate;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\JournalLine;

class Accounts extends Controller
{
    protected array $typeLabels = [
        'asset' => 'double-entry::general.assets',
        'liability' => 'double-entry::general.liabilities',
        'equity' => 'double-entry::general.equity',
        'income' => 'double-entry::general.income',
        'expense' => 'double-entry::general.expenses',
    ];

    public function index()
    {
        $accounts = Account::query()
            ->byCompany()
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $groupedAccounts = [];

        foreach (array_keys($this->typeLabels) as $type) {
            $groupedAccounts[$type] = $this->buildHierarchy($accounts->where('type', $type));
        }

        return view('double-entry::accounts.index', compact('groupedAccounts'));
    }

    public function create()
    {
        $selectedType = old('type', 'asset');

        return view('double-entry::accounts.create', [
            'types' => $this->typeOptions(),
            'parentOptionsByType' => $this->parentOptionsByType(),
            'selectedType' => $selectedType,
        ]);
    }

    public function store(AccountStore $request): RedirectResponse
    {
        Account::create([
            'company_id' => company_id(),
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'type' => $request->string('type')->toString(),
            'description' => $request->input('description'),
            'opening_balance' => $request->input('opening_balance', 0) ?: 0,
            'enabled' => $request->boolean('enabled', true),
        ]);

        flash(trans('messages.success.added', ['type' => trans('double-entry::general.account')]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function edit(int $id)
    {
        $account = Account::query()->byCompany()->findOrFail($id);
        $selectedType = old('type', $account->type);

        return view('double-entry::accounts.edit', [
            'account' => $account,
            'types' => $this->typeOptions(),
            'parentOptionsByType' => $this->parentOptionsByType($account->id),
            'selectedType' => $selectedType,
        ]);
    }

    public function update(AccountUpdate $request, int $id): RedirectResponse
    {
        $account = Account::query()->byCompany()->findOrFail($id);

        $account->update([
            'parent_id' => $request->integer('parent_id') ?: null,
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'type' => $request->string('type')->toString(),
            'description' => $request->input('description'),
            'opening_balance' => $request->input('opening_balance', 0) ?: 0,
            'enabled' => $request->boolean('enabled', false),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('double-entry::general.account')]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $account = Account::query()
            ->byCompany()
            ->withCount(['children' => fn ($query) => $query->where('company_id', company_id())])
            ->findOrFail($id);

        if ($account->children_count > 0) {
            flash(trans('double-entry::general.messages.delete_has_children'))->error();

            return redirect()->route('double-entry.accounts.index');
        }

        $hasJournalLines = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('double_entry_journals')
                    ->whereColumn('double_entry_journals.id', 'double_entry_journal_lines.journal_id')
                    ->where('double_entry_journals.company_id', company_id())
                    ->whereNull('double_entry_journals.deleted_at');
            })
            ->exists();

        if ($hasJournalLines) {
            flash(trans('double-entry::general.messages.delete_has_journal_lines'))->error();

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

    public function importProcess(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if (! $handle) {
            flash(trans('double-entry::general.messages.import_failed'))->error();

            return redirect()->back();
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            flash(trans('double-entry::general.messages.import_failed'))->error();

            return redirect()->back();
        }

        $headerMap = $this->normalizeHeaderMap($header);
        $requiredHeaders = ['account', 'type', 'description', 'balance'];

        foreach ($requiredHeaders as $requiredHeader) {
            if (! array_key_exists($requiredHeader, $headerMap)) {
                fclose($handle);
                flash(trans('double-entry::general.messages.import_invalid_columns'))->error();

                return redirect()->back();
            }
        }

        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowData = $this->mapCsvRow($row, $headerMap);
            $type = $this->normalizeImportType($rowData['type'] ?? '');

            if (! $type || empty($rowData['account'])) {
                continue;
            }

            $account = $this->importAccountPath(
                trim((string) $rowData['account']),
                $type,
                $rowData['description'] ?? null,
                $this->normalizeBalance($rowData['balance'] ?? 0)
            );

            if ($account) {
                $imported++;
            }
        }

        fclose($handle);

        flash(trans('double-entry::general.messages.import_success', ['count' => $imported]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    public function toggle(int $id): RedirectResponse
    {
        $account = Account::query()->byCompany()->findOrFail($id);
        $account->enabled = ! $account->enabled;
        $account->save();

        flash(trans('double-entry::general.messages.toggled', [
            'type' => trans('double-entry::general.account'),
            'status' => $account->enabled ? trans('general.enabled') : trans('general.disabled'),
        ]))->success();

        return redirect()->route('double-entry.accounts.index');
    }

    protected function buildHierarchy(Collection $accounts, ?int $parentId = null, int $depth = 0): array
    {
        $rows = [];

        foreach ($accounts->where('parent_id', $parentId)->sortBy('code') as $account) {
            $rows[] = [
                'account' => $account,
                'depth' => $depth,
            ];

            $rows = array_merge($rows, $this->buildHierarchy($accounts, $account->id, $depth + 1));
        }

        return $rows;
    }

    protected function typeOptions(): array
    {
        return [
            'asset' => trans('double-entry::general.asset'),
            'liability' => trans('double-entry::general.liability'),
            'equity' => trans('double-entry::general.equity'),
            'income' => trans('double-entry::general.income'),
            'expense' => trans('double-entry::general.expense'),
        ];
    }

    protected function parentOptionsByType(?int $excludeId = null): array
    {
        $accounts = Account::query()
            ->byCompany()
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $options = [];

        foreach (array_keys($this->typeOptions()) as $type) {
            $options[$type] = [];

            foreach ($this->buildHierarchy($accounts->where('type', $type)) as $row) {
                /** @var \Modules\DoubleEntry\Models\Account $account */
                $account = $row['account'];
                $options[$type][$account->id] = str_repeat('— ', $row['depth']) . $account->code . ' - ' . $account->name;
            }
        }

        return $options;
    }

    protected function normalizeHeaderMap(array $header): array
    {
        $map = [];

        foreach ($header as $index => $column) {
            $normalized = strtolower(trim((string) $column));
            $map[$normalized] = $index;
        }

        return $map;
    }

    protected function mapCsvRow(array $row, array $headerMap): array
    {
        $mapped = [];

        foreach ($headerMap as $column => $index) {
            $mapped[$column] = $row[$index] ?? null;
        }

        return $mapped;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeImportType(string $type): ?string
    {
        $normalized = strtolower(trim($type));

        return match (true) {
            str_contains($normalized, 'asset'),
            str_contains($normalized, 'receivable'),
            str_contains($normalized, 'bank') => 'asset',
            str_contains($normalized, 'liabilit'),
            str_contains($normalized, 'payable'),
            str_contains($normalized, 'credit card') => 'liability',
            str_contains($normalized, 'equity') => 'equity',
            str_contains($normalized, 'income'),
            str_contains($normalized, 'revenue') => 'income',
            str_contains($normalized, 'expense'),
            str_contains($normalized, 'cost of goods sold'),
            str_contains($normalized, 'cogs') => 'expense',
            default => in_array($normalized, array_keys($this->typeOptions()), true) ? $normalized : null,
        };
    }

    protected function normalizeBalance($balance): float
    {
        $value = str_replace([',', '$', ' '], '', (string) $balance);

        return round((float) $value, 4);
    }

    protected function importAccountPath(string $path, string $type, ?string $description, float $balance): ?Account
    {
        $segments = array_values(array_filter(array_map('trim', preg_split('/\s*:\s*/', $path))));

        if (empty($segments)) {
            return null;
        }

        $parentId = null;
        $account = null;
        $lastSegmentIndex = count($segments) - 1;

        foreach ($segments as $index => $segment) {
            $account = Account::query()
                ->byCompany()
                ->where('type', $type)
                ->where('parent_id', $parentId)
                ->where('name', $segment)
                ->first();

            if (! $account) {
                $account = Account::create([
                    'company_id' => company_id(),
                    'parent_id' => $parentId,
                    'code' => $this->nextGeneratedCode($type),
                    'name' => $segment,
                    'type' => $type,
                    'description' => $index === $lastSegmentIndex ? $description : null,
                    'opening_balance' => $index === $lastSegmentIndex ? $balance : 0,
                    'enabled' => true,
                ]);
            } elseif ($index === $lastSegmentIndex) {
                $account->update([
                    'description' => $description ?: $account->description,
                    'opening_balance' => $balance,
                ]);
            }

            $parentId = $account->id;
        }

        return $account;
    }

    protected function nextGeneratedCode(string $type): string
    {
        $ranges = [
            'asset' => 1000,
            'liability' => 2000,
            'equity' => 3000,
            'income' => 4000,
            'expense' => 5000,
        ];

        $start = $ranges[$type] ?? 9000;
        $latestCode = Account::query()
            ->byCompany()
            ->where('type', $type)
            ->whereRaw('code REGEXP "^[0-9]+$"')
            ->selectRaw('MAX(CAST(code AS UNSIGNED)) as max_code')
            ->value('max_code');

        $next = $latestCode ? ((int) $latestCode + 10) : $start;

        return (string) $next;
    }
}
