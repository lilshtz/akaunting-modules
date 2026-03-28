<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\BankFeeds\Http\Requests\ImportUpload;
use Modules\BankFeeds\Models\Import;
use Modules\BankFeeds\Models\Transaction;
use Modules\BankFeeds\Services\CsvParser;
use Modules\DoubleEntry\Models\Account;

class Imports extends Controller
{
    public function __construct(protected CsvParser $parser)
    {
    }

    public function index()
    {
        $imports = Import::query()
            ->byCompany()
            ->with('bankAccount')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('bank-feeds::imports.index', compact('imports'));
    }

    public function create()
    {
        $bankAccounts = Account::query()
            ->byCompany()
            ->where('type', 'asset')
            ->where('enabled', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account) => [$account->id => trim($account->code . ' - ' . $account->name)])
            ->all();

        return view('bank-feeds::imports.create', compact('bankAccounts'));
    }

    public function upload(ImportUpload $request): RedirectResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('bank-feeds/' . company_id(), 'local');

        $import = Import::create([
            'company_id' => company_id(),
            'bank_account_id' => $request->integer('bank_account_id') ?: null,
            'filename' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'format' => 'csv',
            'status' => 'pending',
        ]);

        return redirect()->route('bank-feeds.imports.map', $import->id);
    }

    public function mapColumns(int $id)
    {
        $import = Import::query()->byCompany()->findOrFail($id);

        if (! Storage::disk('local')->exists($import->filename)) {
            abort(404);
        }

        $headers = $this->parser->parseHeaders(Storage::disk('local')->path($import->filename));
        $savedMapping = [];

        if ($import->bank_account_id) {
            $savedMapping = Import::query()
                ->byCompany()
                ->where('bank_account_id', $import->bank_account_id)
                ->whereNotNull('column_mapping')
                ->where('id', '!=', $import->id)
                ->latest('id')
                ->value('column_mapping') ?? [];
        }

        return view('bank-feeds::imports.map-columns', compact('import', 'headers', 'savedMapping'));
    }

    public function process(Request $request, int $id): RedirectResponse
    {
        $import = Import::query()->byCompany()->findOrFail($id);
        $mapping = $request->validate([
            'mapping.date' => ['required', 'integer', 'min:0'],
            'mapping.description' => ['required', 'integer', 'min:0'],
            'mapping.type' => ['nullable', 'integer', 'min:0'],
            'mapping.amount' => ['nullable', 'integer', 'min:0'],
            'mapping.debit' => ['nullable', 'integer', 'min:0'],
            'mapping.credit' => ['nullable', 'integer', 'min:0'],
        ])['mapping'];

        if (
            (($mapping['amount'] ?? '') === '')
            && (($mapping['debit'] ?? '') === '')
            && (($mapping['credit'] ?? '') === '')
        ) {
            return back()->withErrors([
                'mapping.amount' => trans('bank-feeds::general.messages.amount_mapping_required'),
            ])->withInput();
        }

        try {
            $path = Storage::disk('local')->path($import->filename);
            $rows = $this->parser->parseRows($path, $mapping);

            $import->update([
                'status' => 'processing',
                'column_mapping' => $mapping,
                'error_message' => null,
            ]);

            DB::transaction(function () use ($import, $rows): void {
                Transaction::query()
                    ->byCompany()
                    ->where('import_id', $import->id)
                    ->delete();

                foreach ($rows as $row) {
                    $hash = hash('sha256', $row['date'] . '|' . $row['amount'] . '|' . $row['description']);

                    $duplicateExists = Transaction::query()
                        ->byCompany()
                        ->where('duplicate_hash', $hash)
                        ->exists();

                    Transaction::create([
                        'company_id' => company_id(),
                        'import_id' => $import->id,
                        'bank_account_id' => $import->bank_account_id,
                        'date' => $row['date'],
                        'description' => $row['description'],
                        'amount' => $row['amount'],
                        'type' => $row['type'],
                        'raw_data_json' => $row['raw_data_json'],
                        'status' => 'pending',
                        'duplicate_hash' => $hash,
                        'is_duplicate' => $duplicateExists,
                    ]);
                }
            });

            $import->update([
                'row_count' => count($rows),
                'status' => 'complete',
                'imported_at' => now(),
            ]);

            flash(trans('bank-feeds::general.messages.import_success', ['count' => count($rows)]))->success();

            return redirect()->route('bank-feeds.transactions.index', ['import' => $import->id]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            flash(trans('bank-feeds::general.messages.import_failed', ['error' => $e->getMessage()]))->error();

            return redirect()->route('bank-feeds.imports.map', $import->id);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $import = Import::query()->byCompany()->findOrFail($id);

        if ($import->filename && Storage::disk('local')->exists($import->filename)) {
            Storage::disk('local')->delete($import->filename);
        }

        $import->delete();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.import')]))->success();

        return redirect()->route('bank-feeds.imports.index');
    }
}
