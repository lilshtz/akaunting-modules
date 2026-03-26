<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Services\CsvImportService;
use Modules\BankFeeds\Services\OfxImportService;
use Modules\BankFeeds\Services\CategorizationService;

class Imports extends Controller
{
    protected CsvImportService $csvService;
    protected OfxImportService $ofxService;
    protected CategorizationService $categorizationService;

    public function __construct(
        CsvImportService $csvService,
        OfxImportService $ofxService,
        CategorizationService $categorizationService
    ) {
        $this->csvService = $csvService;
        $this->ofxService = $ofxService;
        $this->categorizationService = $categorizationService;
    }

    public function index()
    {
        $imports = BankFeedImport::where('company_id', company_id())
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('bank-feeds::imports.index', compact('imports'));
    }

    public function create()
    {
        return view('bank-feeds::imports.create');
    }

    /**
     * Upload a file and redirect to column mapping (CSV) or process directly (OFX/QFX).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'bank_account_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'ofx', 'qfx'])) {
            flash(trans('bank-feeds::general.messages.invalid_format'))->error();
            return redirect()->back();
        }

        $companyId = company_id();
        $path = $file->store('bank-feeds/' . $companyId, 'public');

        $import = BankFeedImport::create([
            'company_id' => $companyId,
            'bank_account_id' => $request->get('bank_account_id'),
            'filename' => $file->getClientOriginalName(),
            'format' => $extension,
            'status' => BankFeedImport::STATUS_PENDING,
        ]);

        if ($extension === 'csv') {
            // Show column mapping UI
            $fullPath = Storage::disk('public')->path($path);
            $preview = $this->csvService->preview($fullPath);

            // Check for saved mapping
            $savedMapping = $this->getSavedMapping($companyId, $request->get('bank_account_id'));

            return view('bank-feeds::imports.map-columns', compact('import', 'preview', 'path', 'savedMapping'));
        }

        // OFX/QFX — process directly
        return $this->processOfx($import, $path);
    }

    /**
     * Process CSV import with column mapping.
     */
    public function mapColumns(Request $request, int $id)
    {
        $import = BankFeedImport::where('company_id', company_id())->findOrFail($id);

        $request->validate([
            'path' => 'required|string',
            'mapping.date' => 'required|integer|min:0',
            'mapping.description' => 'required|integer|min:0',
            'save_mapping' => 'nullable|boolean',
        ]);

        $mapping = $request->get('mapping');
        $path = $request->get('path');
        $fullPath = Storage::disk('public')->path($path);

        $import->update([
            'status' => BankFeedImport::STATUS_PROCESSING,
            'column_mapping' => $mapping,
        ]);

        // Save mapping for reuse if requested
        if ($request->get('save_mapping')) {
            $this->saveMapping(company_id(), $import->bank_account_id, $mapping);
        }

        try {
            $rowCount = $this->csvService->import($import, $fullPath, $mapping);

            $import->update([
                'status' => BankFeedImport::STATUS_COMPLETE,
                'row_count' => $rowCount,
                'imported_at' => now(),
            ]);

            // Auto-categorize
            $categorized = $this->categorizationService->categorizeImport($import->id, company_id());

            // Clean up uploaded file
            Storage::disk('public')->delete($path);

            flash(trans('bank-feeds::general.messages.import_success', [
                'count' => $rowCount,
                'categorized' => $categorized,
            ]))->success();
        } catch (\Exception $e) {
            $import->update(['status' => BankFeedImport::STATUS_FAILED]);
            Storage::disk('public')->delete($path);

            flash(trans('bank-feeds::general.messages.import_failed', ['error' => $e->getMessage()]))->error();
        }

        return redirect()->route('bank-feeds.imports.index');
    }

    /**
     * Process an OFX/QFX import.
     */
    protected function processOfx(BankFeedImport $import, string $path)
    {
        $fullPath = Storage::disk('public')->path($path);

        $import->update(['status' => BankFeedImport::STATUS_PROCESSING]);

        try {
            $rowCount = $this->ofxService->import($import, $fullPath);

            $import->update([
                'status' => BankFeedImport::STATUS_COMPLETE,
                'row_count' => $rowCount,
                'imported_at' => now(),
            ]);

            // Auto-categorize
            $categorized = $this->categorizationService->categorizeImport($import->id, company_id());

            Storage::disk('public')->delete($path);

            flash(trans('bank-feeds::general.messages.import_success', [
                'count' => $rowCount,
                'categorized' => $categorized,
            ]))->success();
        } catch (\Exception $e) {
            $import->update(['status' => BankFeedImport::STATUS_FAILED]);
            Storage::disk('public')->delete($path);

            flash(trans('bank-feeds::general.messages.import_failed', ['error' => $e->getMessage()]))->error();
        }

        return redirect()->route('bank-feeds.imports.index');
    }

    public function destroy(int $id)
    {
        $import = BankFeedImport::where('company_id', company_id())->findOrFail($id);

        // Delete associated transactions
        $import->transactions()->delete();
        $import->delete();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.import')]))->success();

        return redirect()->route('bank-feeds.imports.index');
    }

    /**
     * Get saved column mapping for a bank account.
     */
    protected function getSavedMapping(int $companyId, int $bankAccountId): ?array
    {
        $key = "bank_feeds.mapping.{$companyId}.{$bankAccountId}";
        $value = setting($key);

        return $value ? json_decode($value, true) : null;
    }

    /**
     * Save column mapping for a bank account.
     */
    protected function saveMapping(int $companyId, int $bankAccountId, array $mapping): void
    {
        $key = "bank_feeds.mapping.{$companyId}.{$bankAccountId}";
        setting([$key => json_encode($mapping)]);
        setting()->save();
    }
}
