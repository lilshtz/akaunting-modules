<?php

namespace Modules\Receipts\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Modules\Receipts\Http\Requests\ReceiptStore;
use Modules\Receipts\Http\Requests\ReceiptUpdate;
use Modules\Receipts\Models\Receipt;
use Modules\Receipts\Services\CategorizationService;
use Modules\Receipts\Services\DuplicateDetectionService;
use Modules\Receipts\Services\OcrService;

class Receipts extends Controller
{
    protected OcrService $ocrService;
    protected CategorizationService $categorizationService;
    protected DuplicateDetectionService $duplicateService;

    public function __construct(
        OcrService $ocrService,
        CategorizationService $categorizationService,
        DuplicateDetectionService $duplicateService
    ) {
        $this->ocrService = $ocrService;
        $this->categorizationService = $categorizationService;
        $this->duplicateService = $duplicateService;
    }

    /**
     * Receipt inbox: grid of uploaded receipts with thumbnails, status filter.
     */
    public function index(Request $request)
    {
        $query = Receipt::where('company_id', company_id())
            ->with(['category']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate(24);

        $statusCounts = Receipt::where('company_id', company_id())
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return $this->response('receipts::receipts.index', compact('receipts', 'statusCounts'));
    }

    /**
     * Drag-drop upload form.
     */
    public function upload()
    {
        return view('receipts::receipts.upload');
    }

    /**
     * Save image, run OCR, save extracted data.
     */
    public function store(ReceiptStore $request)
    {
        $file = $request->file('image');
        $companyId = company_id();

        // Store the image
        $imagePath = $file->store('receipts/' . $companyId, 'public');

        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($file, $companyId);

        // Run OCR
        $fullPath = Storage::disk('public')->path($imagePath);
        $ocrData = $this->ocrService->extract($fullPath);

        // Auto-categorize
        $receipt = new Receipt([
            'company_id' => $companyId,
            'vendor_name' => $ocrData['vendor_name'],
        ]);
        $receipt->company_id = $companyId;
        $categorization = $this->categorizationService->categorize($receipt);

        // Create receipt record
        $receipt = Receipt::create([
            'company_id' => $companyId,
            'image_path' => $imagePath,
            'thumbnail_path' => $thumbnailPath,
            'ocr_raw_json' => $ocrData['raw'],
            'vendor_name' => $ocrData['vendor_name'],
            'receipt_date' => $ocrData['date'],
            'amount' => $ocrData['amount'],
            'tax_amount' => $ocrData['tax_amount'],
            'currency' => $ocrData['currency'] ?? setting('default.currency', 'USD'),
            'category_id' => $categorization['category_id'],
            'status' => Receipt::STATUS_UPLOADED,
            'notes' => $request->get('notes'),
            'created_by' => auth()->id(),
        ]);

        // Check for duplicates
        $duplicates = $this->duplicateService->findDuplicates(
            $companyId,
            $receipt->vendor_name,
            $receipt->amount,
            $receipt->receipt_date?->toDateString(),
            $receipt->id
        );

        if ($duplicates->isNotEmpty()) {
            flash(trans('receipts::general.messages.duplicate_warning', [
                'count' => $duplicates->count(),
            ]))->warning();
        }

        flash(trans('messages.success.added', ['type' => trans('receipts::general.receipt')]))->success();

        return redirect()->route('receipts.receipts.review', $receipt->id);
    }

    /**
     * Show extracted data with editable fields, original image side-by-side.
     */
    public function review(int $id)
    {
        $receipt = Receipt::where('company_id', company_id())
            ->with(['category'])
            ->findOrFail($id);

        $categories = \App\Models\Setting\Category::where('company_id', company_id())
            ->where('type', 'expense')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $duplicates = $this->duplicateService->findDuplicates(
            $receipt->company_id,
            $receipt->vendor_name,
            $receipt->amount,
            $receipt->receipt_date?->toDateString(),
            $receipt->id
        );

        return view('receipts::receipts.review', compact('receipt', 'categories', 'duplicates'));
    }

    /**
     * Update receipt data after review.
     */
    public function update(ReceiptUpdate $request, int $id)
    {
        $receipt = Receipt::where('company_id', company_id())->findOrFail($id);

        $receipt->update([
            'vendor_name' => $request->get('vendor_name'),
            'receipt_date' => $request->get('receipt_date'),
            'amount' => $request->get('amount'),
            'tax_amount' => $request->get('tax_amount'),
            'currency' => $request->get('currency'),
            'category_id' => $request->get('category_id'),
            'notes' => $request->get('notes'),
            'status' => Receipt::STATUS_REVIEWED,
        ]);

        flash(trans('messages.success.updated', ['type' => trans('receipts::general.receipt')]))->success();

        return redirect()->route('receipts.receipts.review', $receipt->id);
    }

    /**
     * Show process form — create bill or payment from receipt data.
     */
    public function process(int $id)
    {
        $receipt = Receipt::where('company_id', company_id())
            ->with(['category'])
            ->findOrFail($id);

        if (!in_array($receipt->status, [Receipt::STATUS_UPLOADED, Receipt::STATUS_REVIEWED])) {
            flash(trans('receipts::general.messages.already_processed'))->warning();
            return redirect()->route('receipts.receipts.index');
        }

        $categories = \App\Models\Setting\Category::where('company_id', company_id())
            ->where('type', 'expense')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $accounts = \App\Models\Banking\Account::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        $contacts = \App\Models\Common\Contact::where('company_id', company_id())
            ->where('type', 'vendor')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('receipts::receipts.process', compact('receipt', 'categories', 'accounts', 'contacts'));
    }

    /**
     * Create bill or payment from receipt.
     */
    public function processStore(Request $request, int $id)
    {
        $receipt = Receipt::where('company_id', company_id())->findOrFail($id);
        $companyId = company_id();
        $entityType = $request->get('entity_type', 'bill');

        if ($entityType === 'bill') {
            $transaction = $this->createBillFromReceipt($receipt, $request);
        } else {
            $transaction = $this->createPaymentFromReceipt($receipt, $request);
        }

        $receipt->update([
            'status' => Receipt::STATUS_PROCESSED,
            'transaction_id' => $transaction->id,
        ]);

        flash(trans('receipts::general.messages.processed_success'))->success();

        return redirect()->route('receipts.receipts.index');
    }

    /**
     * Delete receipt.
     */
    public function destroy(int $id)
    {
        $receipt = Receipt::where('company_id', company_id())->findOrFail($id);

        // Delete associated files
        if ($receipt->image_path) {
            Storage::disk('public')->delete($receipt->image_path);
        }
        if ($receipt->thumbnail_path) {
            Storage::disk('public')->delete($receipt->thumbnail_path);
        }

        $receipt->delete();

        flash(trans('messages.success.deleted', ['type' => trans('receipts::general.receipt')]))->success();

        return redirect()->route('receipts.receipts.index');
    }

    /**
     * Upload multiple images at once.
     */
    public function bulkUpload()
    {
        return view('receipts::receipts.bulk-upload');
    }

    /**
     * Store multiple uploaded images.
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,bmp,tiff,webp|max:10240',
        ]);

        $companyId = company_id();
        $uploaded = 0;
        $duplicateWarnings = 0;

        foreach ($request->file('images') as $file) {
            $imagePath = $file->store('receipts/' . $companyId, 'public');
            $thumbnailPath = $this->generateThumbnail($file, $companyId);

            $fullPath = Storage::disk('public')->path($imagePath);
            $ocrData = $this->ocrService->extract($fullPath);

            $tempReceipt = new Receipt([
                'company_id' => $companyId,
                'vendor_name' => $ocrData['vendor_name'],
            ]);
            $tempReceipt->company_id = $companyId;
            $categorization = $this->categorizationService->categorize($tempReceipt);

            $receipt = Receipt::create([
                'company_id' => $companyId,
                'image_path' => $imagePath,
                'thumbnail_path' => $thumbnailPath,
                'ocr_raw_json' => $ocrData['raw'],
                'vendor_name' => $ocrData['vendor_name'],
                'receipt_date' => $ocrData['date'],
                'amount' => $ocrData['amount'],
                'tax_amount' => $ocrData['tax_amount'],
                'currency' => $ocrData['currency'] ?? setting('default.currency', 'USD'),
                'category_id' => $categorization['category_id'],
                'status' => Receipt::STATUS_UPLOADED,
                'created_by' => auth()->id(),
            ]);

            $duplicates = $this->duplicateService->findDuplicates(
                $companyId,
                $receipt->vendor_name,
                $receipt->amount,
                $receipt->receipt_date?->toDateString(),
                $receipt->id
            );

            if ($duplicates->isNotEmpty()) {
                $duplicateWarnings++;
            }

            $uploaded++;
        }

        flash(trans('receipts::general.messages.bulk_uploaded', ['count' => $uploaded]))->success();

        if ($duplicateWarnings > 0) {
            flash(trans('receipts::general.messages.bulk_duplicates', ['count' => $duplicateWarnings]))->warning();
        }

        return redirect()->route('receipts.receipts.index');
    }

    /**
     * Process all reviewed receipts.
     */
    public function bulkProcess(Request $request)
    {
        $receiptIds = $request->get('receipt_ids', []);

        if (empty($receiptIds)) {
            $receipts = Receipt::where('company_id', company_id())
                ->where('status', Receipt::STATUS_REVIEWED)
                ->get();
        } else {
            $receipts = Receipt::where('company_id', company_id())
                ->whereIn('id', $receiptIds)
                ->whereIn('status', [Receipt::STATUS_UPLOADED, Receipt::STATUS_REVIEWED])
                ->get();
        }

        $processed = 0;
        foreach ($receipts as $receipt) {
            if ($receipt->amount && $receipt->vendor_name) {
                $transaction = $this->createBillFromReceipt($receipt, $request);
                $receipt->update([
                    'status' => Receipt::STATUS_PROCESSED,
                    'transaction_id' => $transaction->id,
                ]);
                $processed++;
            }
        }

        flash(trans('receipts::general.messages.bulk_processed', ['count' => $processed]))->success();

        return redirect()->route('receipts.receipts.index');
    }

    /**
     * Create a bill from a receipt.
     */
    protected function createBillFromReceipt(Receipt $receipt, Request $request)
    {
        $contactId = $request->get('contact_id');
        $categoryId = $request->get('category_id', $receipt->category_id);
        $accountId = $request->get('account_id');

        // Find or create vendor contact
        if (!$contactId && $receipt->vendor_name) {
            $contact = \App\Models\Common\Contact::firstOrCreate(
                [
                    'company_id' => $receipt->company_id,
                    'type' => 'vendor',
                    'name' => $receipt->vendor_name,
                ],
                [
                    'enabled' => true,
                    'currency_code' => $receipt->currency ?? setting('default.currency', 'USD'),
                ]
            );
            $contactId = $contact->id;
        }

        // Create the bill document
        $bill = \App\Models\Document\Document::create([
            'company_id' => $receipt->company_id,
            'type' => 'bill',
            'document_number' => $this->generateBillNumber($receipt->company_id),
            'status' => 'received',
            'issued_at' => $receipt->receipt_date ?? now(),
            'due_at' => $receipt->receipt_date ? $receipt->receipt_date->addDays(30) : now()->addDays(30),
            'amount' => $receipt->amount,
            'currency_code' => $receipt->currency ?? setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'category_id' => $categoryId,
            'contact_id' => $contactId,
            'contact_name' => $receipt->vendor_name,
            'notes' => $receipt->notes ?? trans('receipts::general.messages.created_from_receipt', ['id' => $receipt->id]),
        ]);

        // Create bill line item
        \App\Models\Document\DocumentItem::create([
            'company_id' => $receipt->company_id,
            'type' => 'bill',
            'document_id' => $bill->id,
            'name' => $receipt->vendor_name . ' - ' . trans('receipts::general.receipt'),
            'quantity' => 1,
            'price' => $receipt->amount ?? 0,
            'tax' => $receipt->tax_amount ?? 0,
            'total' => $receipt->amount ?? 0,
        ]);

        // Create bill totals
        \App\Models\Document\DocumentTotal::create([
            'company_id' => $receipt->company_id,
            'type' => 'bill',
            'document_id' => $bill->id,
            'code' => 'sub_total',
            'name' => 'general.sub_total',
            'amount' => ($receipt->amount ?? 0) - ($receipt->tax_amount ?? 0),
            'sort_order' => 1,
        ]);

        if ($receipt->tax_amount) {
            \App\Models\Document\DocumentTotal::create([
                'company_id' => $receipt->company_id,
                'type' => 'bill',
                'document_id' => $bill->id,
                'code' => 'tax',
                'name' => 'general.tax',
                'amount' => $receipt->tax_amount,
                'sort_order' => 2,
            ]);
        }

        \App\Models\Document\DocumentTotal::create([
            'company_id' => $receipt->company_id,
            'type' => 'bill',
            'document_id' => $bill->id,
            'code' => 'total',
            'name' => 'general.total',
            'amount' => $receipt->amount ?? 0,
            'sort_order' => 3,
        ]);

        return $bill;
    }

    /**
     * Create a payment transaction from a receipt.
     */
    protected function createPaymentFromReceipt(Receipt $receipt, Request $request)
    {
        $contactId = $request->get('contact_id');
        $categoryId = $request->get('category_id', $receipt->category_id);
        $accountId = $request->get('account_id', setting('default.account'));

        // Find or create vendor contact
        if (!$contactId && $receipt->vendor_name) {
            $contact = \App\Models\Common\Contact::firstOrCreate(
                [
                    'company_id' => $receipt->company_id,
                    'type' => 'vendor',
                    'name' => $receipt->vendor_name,
                ],
                [
                    'enabled' => true,
                    'currency_code' => $receipt->currency ?? setting('default.currency', 'USD'),
                ]
            );
            $contactId = $contact->id;
        }

        $transaction = \App\Models\Banking\Transaction::create([
            'company_id' => $receipt->company_id,
            'type' => 'expense',
            'paid_at' => $receipt->receipt_date ?? now(),
            'amount' => $receipt->amount ?? 0,
            'currency_code' => $receipt->currency ?? setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'account_id' => $accountId,
            'contact_id' => $contactId,
            'category_id' => $categoryId,
            'description' => $receipt->vendor_name . ' - ' . trans('receipts::general.receipt'),
            'payment_method' => 'offline-payments.cash.1',
            'reference' => trans('receipts::general.messages.created_from_receipt', ['id' => $receipt->id]),
        ]);

        return $transaction;
    }

    protected function generateBillNumber(int $companyId): string
    {
        $prefix = setting('bill.number_prefix', 'BILL-');
        $next = setting('bill.number_next', '1');

        $number = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);

        // Increment for next use
        setting(['bill.number_next' => (int) $next + 1]);
        setting()->save();

        return $number;
    }

    protected function generateThumbnail($file, int $companyId): ?string
    {
        try {
            $thumbnailDir = 'receipts/' . $companyId . '/thumbnails';
            Storage::disk('public')->makeDirectory($thumbnailDir);

            $filename = pathinfo($file->hashName(), PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnailPath = $thumbnailDir . '/' . $filename;

            $image = Image::make($file->getRealPath());
            $image->fit(200, 200);

            Storage::disk('public')->put($thumbnailPath, $image->encode('jpg', 80)->getEncoded());

            return $thumbnailPath;
        } catch (\Exception $e) {
            // Thumbnail generation is non-critical
            return null;
        }
    }
}
