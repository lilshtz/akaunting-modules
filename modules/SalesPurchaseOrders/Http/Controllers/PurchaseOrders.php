<?php

namespace Modules\SalesPurchaseOrders\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentItemTax;
use App\Models\Document\DocumentTotal;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\SalesPurchaseOrders\Http\Requests\PurchaseOrderStore;
use Modules\SalesPurchaseOrders\Http\Requests\PurchaseOrderUpdate;
use Modules\SalesPurchaseOrders\Models\PurchaseOrder;
use Modules\SalesPurchaseOrders\Models\OrderHistory;
use Modules\SalesPurchaseOrders\Models\OrderSetting;
use Modules\SalesPurchaseOrders\Notifications\OrderSent;

class PurchaseOrders extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'items', 'totals']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->get('contact_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%');
            });
        }

        $purchaseOrders = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [];
        foreach (PurchaseOrder::STATUSES as $status) {
            $statuses[$status] = trans('sales-purchase-orders::general.po_statuses.' . $status);
        }

        $vendors = Contact::where('company_id', company_id())
            ->where('type', 'vendor')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->response('sales-purchase-orders::purchase-orders.index', compact('purchaseOrders', 'statuses', 'vendors'));
    }

    public function create(): Response|mixed
    {
        $vendors = Contact::where('company_id', company_id())
            ->where('type', 'vendor')
            ->orderBy('name')
            ->pluck('name', 'id');

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $categories = Category::where('company_id', company_id())
            ->where('type', 'expense')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $taxes = Tax::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->rate . '%)'];
            })
            ->prepend(trans('general.none'), '');

        $settings = OrderSetting::getForCompany(company_id());

        $defaultCurrency = Currency::where('company_id', company_id())
            ->where('code', setting('default.currency'))
            ->first();

        return view('sales-purchase-orders::purchase-orders.create', compact(
            'vendors', 'currencies', 'categories', 'taxes', 'settings', 'defaultCurrency'
        ));
    }

    public function store(PurchaseOrderStore $request): Response|mixed
    {
        $settings = OrderSetting::getForCompany(company_id());

        $contact = Contact::findOrFail($request->get('contact_id'));

        $purchaseOrder = PurchaseOrder::create([
            'company_id' => company_id(),
            'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
            'document_number' => $settings->generatePurchaseOrderNumber(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'amount' => 0,
            'currency_code' => $request->get('currency_code', setting('default.currency')),
            'currency_rate' => $request->get('currency_rate', 1),
            'category_id' => $request->get('category_id'),
            'contact_id' => $contact->id,
            'contact_name' => $contact->name,
            'contact_email' => $contact->email,
            'contact_tax_number' => $contact->tax_number,
            'contact_phone' => $contact->phone,
            'contact_address' => $contact->address,
            'contact_country' => $contact->country,
            'contact_state' => $contact->state,
            'contact_zip_code' => $contact->zip_code,
            'contact_city' => $contact->city,
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
            'title' => $request->get('title'),
            'subheading' => $request->get('subheading'),
            'notes' => $request->get('notes', $settings->default_terms),
            'footer' => $request->get('footer'),
            'created_from' => 'sales-purchase-orders::purchase-orders',
            'created_by' => auth()->id(),
        ]);

        $this->saveItems($purchaseOrder, $request->get('items'));
        $this->calculateTotals($purchaseOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_created', ['number' => $purchaseOrder->document_number]),
        ]);

        flash(trans('messages.success.added', ['type' => trans('sales-purchase-orders::general.purchase_order')]))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function show(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'orderHistories', 'convertedBill', 'parentSalesOrder'])
            ->findOrFail($id);

        return view('sales-purchase-orders::purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes'])
            ->findOrFail($id);

        if (! $purchaseOrder->isEditable()) {
            flash(trans('sales-purchase-orders::general.messages.not_editable'))->warning();
            return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
        }

        $vendors = Contact::where('company_id', company_id())
            ->where('type', 'vendor')
            ->orderBy('name')
            ->pluck('name', 'id');

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $categories = Category::where('company_id', company_id())
            ->where('type', 'expense')
            ->where('enabled', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $taxes = Tax::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->rate . '%)'];
            })
            ->prepend(trans('general.none'), '');

        return view('sales-purchase-orders::purchase-orders.edit', compact(
            'purchaseOrder', 'vendors', 'currencies', 'categories', 'taxes'
        ));
    }

    public function update(int $id, PurchaseOrderUpdate $request): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())->findOrFail($id);

        if (! $purchaseOrder->isEditable()) {
            flash(trans('sales-purchase-orders::general.messages.not_editable'))->warning();
            return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
        }

        $contact = Contact::findOrFail($request->get('contact_id'));

        $purchaseOrder->update([
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'currency_code' => $request->get('currency_code', $purchaseOrder->currency_code),
            'currency_rate' => $request->get('currency_rate', 1),
            'category_id' => $request->get('category_id'),
            'contact_id' => $contact->id,
            'contact_name' => $contact->name,
            'contact_email' => $contact->email,
            'contact_tax_number' => $contact->tax_number,
            'contact_phone' => $contact->phone,
            'contact_address' => $contact->address,
            'contact_country' => $contact->country,
            'contact_state' => $contact->state,
            'contact_zip_code' => $contact->zip_code,
            'contact_city' => $contact->city,
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
            'title' => $request->get('title'),
            'subheading' => $request->get('subheading'),
            'notes' => $request->get('notes'),
            'footer' => $request->get('footer'),
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        $purchaseOrder->items()->delete();
        $purchaseOrder->item_taxes()->delete();
        $this->saveItems($purchaseOrder, $request->get('items'));
        $this->calculateTotals($purchaseOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_updated', ['number' => $purchaseOrder->document_number]),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('sales-purchase-orders::general.purchase_order')]))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())->findOrFail($id);

        if (! $purchaseOrder->isDeletable()) {
            flash(trans('sales-purchase-orders::general.messages.not_deletable'))->warning();
            return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->item_taxes()->delete();
        $purchaseOrder->orderHistories()->delete();
        $purchaseOrder->totals()->delete();
        $purchaseOrder->delete();

        flash(trans('messages.success.deleted', ['type' => trans('sales-purchase-orders::general.purchase_order')]))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.index');
    }

    public function send(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_SENT]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_SENT,
            'notify' => '1',
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_sent', [
                'number' => $purchaseOrder->document_number,
                'email' => $purchaseOrder->contact_email,
            ]),
        ]);

        if ($purchaseOrder->contact_email) {
            try {
                $purchaseOrder->contact->notify(new OrderSent($purchaseOrder));
            } catch (\Exception $e) {
                report($e);
            }
        }

        flash(trans('sales-purchase-orders::general.messages.sent_success'))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function confirm(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())->findOrFail($id);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CONFIRMED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_CONFIRMED,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_confirmed', [
                'number' => $purchaseOrder->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.confirmed_success'))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function receive(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with('items')
            ->findOrFail($id);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_received', [
                'number' => $purchaseOrder->document_number,
            ]),
        ]);

        // Update inventory stock levels if Inventory module is installed
        if (class_exists(\Modules\Inventory\Models\InventoryItem::class)) {
            foreach ($purchaseOrder->items as $item) {
                if ($item->item_id) {
                    $inventoryItem = \Modules\Inventory\Models\InventoryItem::where('item_id', $item->item_id)->first();

                    if ($inventoryItem) {
                        $inventoryItem->increment('quantity', $item->quantity);
                    }
                }
            }
        }

        flash(trans('sales-purchase-orders::general.messages.received_success'))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function cancel(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())->findOrFail($id);

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_cancelled', [
                'number' => $purchaseOrder->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.cancelled_success'))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function convertBill(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        if (! in_array($purchaseOrder->status, [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_RECEIVED])) {
            flash(trans('sales-purchase-orders::general.messages.must_be_confirmed_or_received'))->warning();
            return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
        }

        $bill = Document::create([
            'company_id' => company_id(),
            'type' => Document::BILL_TYPE,
            'document_number' => $this->getNextBillNumber(),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'amount' => $purchaseOrder->amount,
            'currency_code' => $purchaseOrder->currency_code,
            'currency_rate' => $purchaseOrder->currency_rate,
            'category_id' => $purchaseOrder->category_id,
            'contact_id' => $purchaseOrder->contact_id,
            'contact_name' => $purchaseOrder->contact_name,
            'contact_email' => $purchaseOrder->contact_email,
            'contact_tax_number' => $purchaseOrder->contact_tax_number,
            'contact_phone' => $purchaseOrder->contact_phone,
            'contact_address' => $purchaseOrder->contact_address,
            'contact_country' => $purchaseOrder->contact_country,
            'contact_state' => $purchaseOrder->contact_state,
            'contact_zip_code' => $purchaseOrder->contact_zip_code,
            'contact_city' => $purchaseOrder->contact_city,
            'discount_type' => $purchaseOrder->discount_type,
            'discount_rate' => $purchaseOrder->discount_rate,
            'notes' => $purchaseOrder->notes,
            'footer' => $purchaseOrder->footer,
            'parent_id' => $purchaseOrder->id,
            'created_from' => 'sales-purchase-orders::purchase-orders.convert-bill',
            'created_by' => auth()->id(),
        ]);

        foreach ($purchaseOrder->items as $item) {
            $billItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => Document::BILL_TYPE,
                'document_id' => $bill->id,
                'item_id' => $item->item_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
                'tax' => $item->tax,
                'discount_rate' => $item->discount_rate,
                'discount_type' => $item->discount_type,
            ]);

            if ($item->taxes) {
                foreach ($item->taxes as $tax) {
                    DocumentItemTax::create([
                        'company_id' => company_id(),
                        'type' => Document::BILL_TYPE,
                        'document_id' => $bill->id,
                        'document_item_id' => $billItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        foreach ($purchaseOrder->totals as $total) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => Document::BILL_TYPE,
                'document_id' => $bill->id,
                'code' => $total->code,
                'name' => $total->name,
                'amount' => $total->amount,
                'sort_order' => $total->sort_order,
            ]);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'description' => trans('sales-purchase-orders::general.messages.converted_to_bill', [
                'number' => $purchaseOrder->document_number,
                'bill' => $bill->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.converted_bill_success', [
            'bill' => $bill->document_number,
        ]))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $purchaseOrder->id);
    }

    public function duplicate(int $id): Response|mixed
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with(['items.taxes'])
            ->findOrFail($id);

        $settings = OrderSetting::getForCompany(company_id());

        $newPurchaseOrder = PurchaseOrder::create([
            'company_id' => company_id(),
            'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
            'document_number' => $settings->generatePurchaseOrderNumber(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'issued_at' => now(),
            'due_at' => $purchaseOrder->due_at ? now()->addDays($purchaseOrder->issued_at->diffInDays($purchaseOrder->due_at)) : null,
            'amount' => 0,
            'currency_code' => $purchaseOrder->currency_code,
            'currency_rate' => $purchaseOrder->currency_rate,
            'category_id' => $purchaseOrder->category_id,
            'contact_id' => $purchaseOrder->contact_id,
            'contact_name' => $purchaseOrder->contact_name,
            'contact_email' => $purchaseOrder->contact_email,
            'contact_tax_number' => $purchaseOrder->contact_tax_number,
            'contact_phone' => $purchaseOrder->contact_phone,
            'contact_address' => $purchaseOrder->contact_address,
            'contact_country' => $purchaseOrder->contact_country,
            'contact_state' => $purchaseOrder->contact_state,
            'contact_zip_code' => $purchaseOrder->contact_zip_code,
            'contact_city' => $purchaseOrder->contact_city,
            'discount_type' => $purchaseOrder->discount_type,
            'discount_rate' => $purchaseOrder->discount_rate,
            'title' => $purchaseOrder->title,
            'subheading' => $purchaseOrder->subheading,
            'notes' => $purchaseOrder->notes,
            'footer' => $purchaseOrder->footer,
            'created_from' => 'sales-purchase-orders::purchase-orders.duplicate',
            'created_by' => auth()->id(),
        ]);

        foreach ($purchaseOrder->items as $item) {
            $newItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_id' => $newPurchaseOrder->id,
                'item_id' => $item->item_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
                'tax' => $item->tax,
                'discount_rate' => $item->discount_rate,
                'discount_type' => $item->discount_type,
            ]);

            if ($item->taxes) {
                foreach ($item->taxes as $tax) {
                    DocumentItemTax::create([
                        'company_id' => company_id(),
                        'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                        'document_id' => $newPurchaseOrder->id,
                        'document_item_id' => $newItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        $this->calculateTotals($newPurchaseOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $newPurchaseOrder->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.purchase_order_duplicated', [
                'number' => $purchaseOrder->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.duplicated_success'))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.show', $newPurchaseOrder->id);
    }

    public function pdf(int $id)
    {
        $purchaseOrder = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->findOrFail($id);

        $html = view('sales-purchase-orders::purchase-orders.pdf', compact('purchaseOrder'))->render();

        $filename = $purchaseOrder->document_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($filename);
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function report(Request $request): Response|mixed
    {
        $query = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'totals']);

        if ($request->filled('start_date')) {
            $query->where('issued_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('issued_at', '<=', $request->get('end_date'));
        }

        $purchaseOrders = $query->orderBy('created_at', 'desc')->get();

        $histories = OrderHistory::where('company_id', company_id())
            ->whereIn('document_id', $purchaseOrders->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        $byVendor = $purchaseOrders->groupBy('contact_id')->map(function ($orders) {
            return [
                'vendor_name' => $orders->first()->contact_name,
                'count' => $orders->count(),
                'total_amount' => $orders->sum('amount'),
                'statuses' => $orders->groupBy('status')->map->count(),
            ];
        })->sortByDesc('total_amount')->values();

        $statusSummary = [];
        foreach (PurchaseOrder::STATUSES as $status) {
            $filtered = $purchaseOrders->where('status', $status);
            $statusSummary[$status] = [
                'count' => $filtered->count(),
                'total' => $filtered->sum('amount'),
            ];
        }

        return view('sales-purchase-orders::purchase-orders.report', compact(
            'purchaseOrders', 'histories', 'byVendor', 'statusSummary'
        ));
    }

    public function import(): Response|mixed
    {
        return view('sales-purchase-orders::purchase-orders.import');
    }

    public function importProcess(Request $request): Response|mixed
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('import_file');
        $settings = OrderSetting::getForCompany(company_id());

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $contact = Contact::where('company_id', company_id())
                ->where('type', 'vendor')
                ->where('name', $data['vendor_name'] ?? '')
                ->first();

            if (! $contact) {
                continue;
            }

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_number' => $settings->generatePurchaseOrderNumber(),
                'status' => PurchaseOrder::STATUS_DRAFT,
                'issued_at' => $data['issued_at'] ?? now(),
                'due_at' => $data['due_at'] ?? null,
                'amount' => 0,
                'currency_code' => $data['currency_code'] ?? setting('default.currency'),
                'currency_rate' => 1,
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'contact_email' => $contact->email,
                'contact_tax_number' => $contact->tax_number,
                'contact_phone' => $contact->phone,
                'contact_address' => $contact->address,
                'contact_country' => $contact->country,
                'contact_state' => $contact->state,
                'contact_zip_code' => $contact->zip_code,
                'contact_city' => $contact->city,
                'notes' => $data['notes'] ?? '',
                'created_from' => 'sales-purchase-orders::purchase-orders.import',
                'created_by' => auth()->id(),
            ]);

            OrderHistory::create([
                'company_id' => company_id(),
                'document_id' => $purchaseOrder->id,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'description' => trans('sales-purchase-orders::general.messages.imported'),
            ]);

            $imported++;
        }

        fclose($handle);

        flash(trans('sales-purchase-orders::general.messages.imported_success', ['count' => $imported]))->success();

        return redirect()->route('sales-purchase-orders.purchase-orders.index');
    }

    public function export(): mixed
    {
        $purchaseOrders = PurchaseOrder::where('company_id', company_id())
            ->with(['contact', 'items', 'totals'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'purchase-orders-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = [
            'document_number',
            'status',
            'issued_at',
            'due_at',
            'amount',
            'currency_code',
            'vendor_name',
            'vendor_email',
            'notes',
        ];

        $callback = function () use ($purchaseOrders, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($purchaseOrders as $purchaseOrder) {
                fputcsv($handle, [
                    $purchaseOrder->document_number,
                    $purchaseOrder->status,
                    $purchaseOrder->issued_at ? $purchaseOrder->issued_at->format('Y-m-d') : '',
                    $purchaseOrder->due_at ? $purchaseOrder->due_at->format('Y-m-d') : '',
                    $purchaseOrder->amount,
                    $purchaseOrder->currency_code,
                    $purchaseOrder->contact_name,
                    $purchaseOrder->contact_email,
                    $purchaseOrder->notes,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function saveItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $price = (float) $item['price'];
            $discountRate = (float) ($item['discount_rate'] ?? 0);

            $subtotal = $quantity * $price;
            if ($discountRate > 0) {
                $subtotal -= $subtotal * ($discountRate / 100);
            }

            $taxAmount = 0;
            $taxId = $item['tax_id'] ?? null;

            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax) {
                    $taxAmount = $subtotal * ($tax->rate / 100);
                }
            }

            $documentItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_id' => $purchaseOrder->id,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $subtotal,
                'tax' => $taxAmount,
                'discount_rate' => $discountRate,
                'discount_type' => 'percentage',
            ]);

            if ($taxId && isset($tax)) {
                DocumentItemTax::create([
                    'company_id' => company_id(),
                    'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                    'document_id' => $purchaseOrder->id,
                    'document_item_id' => $documentItem->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name,
                    'amount' => $taxAmount,
                ]);
            }
        }
    }

    protected function calculateTotals(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->totals()->delete();

        $purchaseOrder->load('items.taxes');

        $subTotal = $purchaseOrder->items->sum('total');
        $taxTotal = $purchaseOrder->items->sum('tax');
        $discountTotal = 0;

        if ($purchaseOrder->discount_rate > 0) {
            if ($purchaseOrder->discount_type === 'percentage') {
                $discountTotal = $subTotal * ($purchaseOrder->discount_rate / 100);
            } else {
                $discountTotal = $purchaseOrder->discount_rate;
            }
        }

        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
            'document_id' => $purchaseOrder->id,
            'code' => 'sub_total',
            'name' => 'sales-purchase-orders::general.sub_total',
            'amount' => $subTotal,
            'sort_order' => 1,
        ]);

        if ($discountTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_id' => $purchaseOrder->id,
                'code' => 'discount',
                'name' => 'sales-purchase-orders::general.discount',
                'amount' => $discountTotal,
                'sort_order' => 2,
            ]);
        }

        if ($taxTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_id' => $purchaseOrder->id,
                'code' => 'tax',
                'name' => 'sales-purchase-orders::general.tax',
                'amount' => $taxTotal,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
            'document_id' => $purchaseOrder->id,
            'code' => 'total',
            'name' => 'sales-purchase-orders::general.total',
            'amount' => $grandTotal,
            'sort_order' => 4,
        ]);

        $purchaseOrder->update(['amount' => $grandTotal]);
    }

    protected function getNextBillNumber(): string
    {
        $prefix = setting('bill.number_prefix', 'BILL-');
        $next = setting('bill.number_next', 1);

        $number = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);

        setting(['bill.number_next' => $next + 1]);
        setting()->save();

        return $number;
    }
}
