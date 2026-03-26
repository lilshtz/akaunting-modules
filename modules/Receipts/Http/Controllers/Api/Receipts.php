<?php

namespace Modules\Receipts\Http\Controllers\Api;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
     * GET /api/receipts — List receipts with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Receipt::where('company_id', company_id());

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('vendor_name')) {
            $query->where('vendor_name', 'like', '%' . $request->get('vendor_name') . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('receipt_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('receipt_date', '<=', $request->get('date_to'));
        }

        $receipts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    /**
     * POST /api/receipts/upload — Multipart image upload, returns receipt_id + OCR data.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,bmp,tiff,webp|max:10240',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('image');
        $companyId = company_id();

        $imagePath = $file->store('receipts/' . $companyId, 'public');

        // Run OCR
        $fullPath = Storage::disk('public')->path($imagePath);
        $ocrData = $this->ocrService->extract($fullPath);

        // Auto-categorize
        $tempReceipt = new Receipt([
            'company_id' => $companyId,
            'vendor_name' => $ocrData['vendor_name'],
        ]);
        $tempReceipt->company_id = $companyId;
        $categorization = $this->categorizationService->categorize($tempReceipt);

        $receipt = Receipt::create([
            'company_id' => $companyId,
            'image_path' => $imagePath,
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

        return response()->json([
            'success' => true,
            'data' => [
                'receipt_id' => $receipt->id,
                'ocr' => [
                    'vendor_name' => $receipt->vendor_name,
                    'date' => $receipt->receipt_date?->toDateString(),
                    'amount' => $receipt->amount,
                    'tax_amount' => $receipt->tax_amount,
                    'currency' => $receipt->currency,
                ],
                'category_id' => $receipt->category_id,
                'has_duplicates' => $duplicates->isNotEmpty(),
                'duplicate_count' => $duplicates->count(),
            ],
        ], 201);
    }

    /**
     * POST /api/receipts/{id}/process — Create transaction from receipt.
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $receipt = Receipt::where('company_id', company_id())->findOrFail($id);

        if ($receipt->status === Receipt::STATUS_PROCESSED || $receipt->status === Receipt::STATUS_MATCHED) {
            return response()->json([
                'success' => false,
                'message' => trans('receipts::general.messages.already_processed'),
            ], 422);
        }

        $entityType = $request->get('entity_type', 'bill');

        // Allow updating fields before processing
        if ($request->filled('vendor_name')) {
            $receipt->vendor_name = $request->get('vendor_name');
        }
        if ($request->filled('amount')) {
            $receipt->amount = $request->get('amount');
        }
        if ($request->filled('receipt_date')) {
            $receipt->receipt_date = $request->get('receipt_date');
        }
        if ($request->filled('category_id')) {
            $receipt->category_id = $request->get('category_id');
        }
        $receipt->save();

        // Use the main controller's processing logic
        $mainController = app(\Modules\Receipts\Http\Controllers\Receipts::class);
        $fakeRequest = new Request($request->all());

        if ($entityType === 'bill') {
            $transaction = $this->createBillFromReceipt($receipt, $request);
        } else {
            $transaction = $this->createPaymentFromReceipt($receipt, $request);
        }

        $receipt->update([
            'status' => Receipt::STATUS_PROCESSED,
            'transaction_id' => $transaction->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'receipt_id' => $receipt->id,
                'transaction_id' => $transaction->id,
                'entity_type' => $entityType,
                'status' => $receipt->status,
            ],
        ]);
    }

    /**
     * GET /api/receipts/pending — Unprocessed receipts count.
     */
    public function pending(): JsonResponse
    {
        $counts = Receipt::where('company_id', company_id())
            ->pending()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'uploaded' => $counts[Receipt::STATUS_UPLOADED] ?? 0,
                'reviewed' => $counts[Receipt::STATUS_REVIEWED] ?? 0,
                'total_pending' => array_sum($counts),
            ],
        ]);
    }

    /**
     * GET /api/receipts/{id} — Get single receipt.
     */
    public function show(int $id): JsonResponse
    {
        $receipt = Receipt::where('company_id', company_id())
            ->with(['category'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    protected function createBillFromReceipt(Receipt $receipt, Request $request)
    {
        $contactId = $request->get('contact_id');
        $categoryId = $request->get('category_id', $receipt->category_id);

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

        $prefix = setting('bill.number_prefix', 'BILL-');
        $next = setting('bill.number_next', '1');
        $number = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        setting(['bill.number_next' => (int) $next + 1]);
        setting()->save();

        $bill = \App\Models\Document\Document::create([
            'company_id' => $receipt->company_id,
            'type' => 'bill',
            'document_number' => $number,
            'status' => 'received',
            'issued_at' => $receipt->receipt_date ?? now(),
            'due_at' => $receipt->receipt_date ? $receipt->receipt_date->addDays(30) : now()->addDays(30),
            'amount' => $receipt->amount,
            'currency_code' => $receipt->currency ?? setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'category_id' => $categoryId,
            'contact_id' => $contactId,
            'contact_name' => $receipt->vendor_name,
            'notes' => trans('receipts::general.messages.created_from_receipt', ['id' => $receipt->id]),
        ]);

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

        return $bill;
    }

    protected function createPaymentFromReceipt(Receipt $receipt, Request $request)
    {
        $contactId = $request->get('contact_id');
        $categoryId = $request->get('category_id', $receipt->category_id);
        $accountId = $request->get('account_id', setting('default.account'));

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

        return \App\Models\Banking\Transaction::create([
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
    }
}
