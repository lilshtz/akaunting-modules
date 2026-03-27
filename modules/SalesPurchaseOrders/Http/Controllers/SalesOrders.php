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
use Modules\SalesPurchaseOrders\Http\Requests\SalesOrderStore;
use Modules\SalesPurchaseOrders\Http\Requests\SalesOrderUpdate;
use Modules\SalesPurchaseOrders\Models\SalesOrder;
use Modules\SalesPurchaseOrders\Models\PurchaseOrder;
use Modules\SalesPurchaseOrders\Models\OrderHistory;
use Modules\SalesPurchaseOrders\Models\OrderSetting;
use Modules\SalesPurchaseOrders\Notifications\OrderSent;

class SalesOrders extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesOrder::where('company_id', company_id())
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

        $salesOrders = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [];
        foreach (SalesOrder::STATUSES as $status) {
            $statuses[$status] = trans('sales-purchase-orders::general.so_statuses.' . $status);
        }

        $customers = Contact::where('company_id', company_id())
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->response('sales-purchase-orders::sales-orders.index', compact('salesOrders', 'statuses', 'customers'));
    }

    public function create(): Response
    {
        $customers = Contact::where('company_id', company_id())
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $categories = Category::where('company_id', company_id())
            ->where('type', 'income')
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

        return view('sales-purchase-orders::sales-orders.create', compact(
            'customers', 'currencies', 'categories', 'taxes', 'settings', 'defaultCurrency'
        ));
    }

    public function store(SalesOrderStore $request): Response
    {
        $settings = OrderSetting::getForCompany(company_id());

        $contact = Contact::where('company_id', company_id())->findOrFail($request->get('contact_id'));

        $salesOrder = SalesOrder::create([
            'company_id' => company_id(),
            'type' => SalesOrder::SALES_ORDER_TYPE,
            'document_number' => $settings->generateSalesOrderNumber(),
            'status' => SalesOrder::STATUS_DRAFT,
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
            'notes' => $request->get('notes', $settings->default_terms ?? ''),
            'footer' => $request->get('footer'),
            'created_from' => 'sales-purchase-orders::sales-orders',
            'created_by' => auth()->id(),
        ]);

        $this->saveItems($salesOrder, $request->get('items'));
        $this->calculateTotals($salesOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.created', ['number' => $salesOrder->document_number]),
        ]);

        flash(trans('messages.success.added', ['type' => trans('sales-purchase-orders::general.sales_order')]))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function show(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'orderHistories', 'convertedInvoice', 'convertedPurchaseOrders'])
            ->findOrFail($id);

        return view('sales-purchase-orders::sales-orders.show', compact('salesOrder'));
    }

    public function edit(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes'])
            ->findOrFail($id);

        if (! $salesOrder->isEditable()) {
            flash(trans('sales-purchase-orders::general.messages.not_editable'))->warning();
            return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
        }

        $customers = Contact::where('company_id', company_id())
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $categories = Category::where('company_id', company_id())
            ->where('type', 'income')
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

        return view('sales-purchase-orders::sales-orders.edit', compact(
            'salesOrder', 'customers', 'currencies', 'categories', 'taxes', 'settings', 'defaultCurrency'
        ));
    }

    public function update(int $id, SalesOrderUpdate $request): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())->findOrFail($id);

        if (! $salesOrder->isEditable()) {
            flash(trans('sales-purchase-orders::general.messages.not_editable'))->warning();
            return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
        }

        $contact = Contact::where('company_id', company_id())->findOrFail($request->get('contact_id'));

        $salesOrder->update([
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'currency_code' => $request->get('currency_code', $salesOrder->currency_code),
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
            'status' => SalesOrder::STATUS_DRAFT,
        ]);

        $salesOrder->items()->delete();
        $salesOrder->item_taxes()->delete();
        $this->saveItems($salesOrder, $request->get('items'));
        $this->calculateTotals($salesOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.updated', ['number' => $salesOrder->document_number]),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('sales-purchase-orders::general.sales_order')]))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function destroy(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())->findOrFail($id);

        if (! $salesOrder->isDeletable()) {
            flash(trans('sales-purchase-orders::general.messages.not_deletable'))->warning();
            return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
        }

        $salesOrder->items()->delete();
        $salesOrder->item_taxes()->delete();
        $salesOrder->orderHistories()->delete();
        $salesOrder->totals()->delete();
        $salesOrder->delete();

        flash(trans('messages.success.deleted', ['type' => trans('sales-purchase-orders::general.sales_order')]))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.index');
    }

    public function send(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $salesOrder->update(['status' => SalesOrder::STATUS_SENT]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_SENT,
            'notify' => '1',
            'description' => trans('sales-purchase-orders::general.messages.sent', [
                'number' => $salesOrder->document_number,
                'email' => $salesOrder->contact_email,
            ]),
        ]);

        if ($salesOrder->contact_email) {
            try {
                $salesOrder->contact->notify(new OrderSent($salesOrder));
            } catch (\Exception $e) {
                report($e);
            }
        }

        flash(trans('sales-purchase-orders::general.messages.sent_success'))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function confirm(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())->findOrFail($id);

        $salesOrder->update(['status' => SalesOrder::STATUS_CONFIRMED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_CONFIRMED,
            'description' => trans('sales-purchase-orders::general.messages.confirmed', ['number' => $salesOrder->document_number]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.confirmed_success'))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function issue(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())->findOrFail($id);

        $salesOrder->update(['status' => SalesOrder::STATUS_ISSUED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_ISSUED,
            'description' => trans('sales-purchase-orders::general.messages.issued', ['number' => $salesOrder->document_number]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.issued_success'))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function cancel(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())->findOrFail($id);

        $salesOrder->update(['status' => SalesOrder::STATUS_CANCELLED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_CANCELLED,
            'description' => trans('sales-purchase-orders::general.messages.cancelled', ['number' => $salesOrder->document_number]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.cancelled_success'))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function convertToInvoice(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        if (! in_array($salesOrder->status, [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_ISSUED])) {
            flash(trans('sales-purchase-orders::general.messages.must_be_confirmed_or_issued'))->warning();
            return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
        }

        $invoice = Document::create([
            'company_id' => company_id(),
            'type' => Document::INVOICE_TYPE,
            'document_number' => $this->getNextInvoiceNumber(),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'amount' => $salesOrder->amount,
            'currency_code' => $salesOrder->currency_code,
            'currency_rate' => $salesOrder->currency_rate,
            'category_id' => $salesOrder->category_id,
            'contact_id' => $salesOrder->contact_id,
            'contact_name' => $salesOrder->contact_name,
            'contact_email' => $salesOrder->contact_email,
            'contact_tax_number' => $salesOrder->contact_tax_number,
            'contact_phone' => $salesOrder->contact_phone,
            'contact_address' => $salesOrder->contact_address,
            'contact_country' => $salesOrder->contact_country,
            'contact_state' => $salesOrder->contact_state,
            'contact_zip_code' => $salesOrder->contact_zip_code,
            'contact_city' => $salesOrder->contact_city,
            'discount_type' => $salesOrder->discount_type,
            'discount_rate' => $salesOrder->discount_rate,
            'notes' => $salesOrder->notes,
            'footer' => $salesOrder->footer,
            'parent_id' => $salesOrder->id,
            'created_from' => 'sales-purchase-orders::sales-orders.convert-invoice',
            'created_by' => auth()->id(),
        ]);

        foreach ($salesOrder->items as $item) {
            $invoiceItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => Document::INVOICE_TYPE,
                'document_id' => $invoice->id,
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
                        'type' => Document::INVOICE_TYPE,
                        'document_id' => $invoice->id,
                        'document_item_id' => $invoiceItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        foreach ($salesOrder->totals as $total) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => Document::INVOICE_TYPE,
                'document_id' => $invoice->id,
                'code' => $total->code,
                'name' => $total->name,
                'amount' => $total->amount,
                'sort_order' => $total->sort_order,
            ]);
        }

        $salesOrder->update(['status' => SalesOrder::STATUS_ISSUED]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => SalesOrder::STATUS_ISSUED,
            'description' => trans('sales-purchase-orders::general.messages.converted_to_invoice', [
                'number' => $salesOrder->document_number,
                'invoice' => $invoice->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.converted_invoice_success', [
            'invoice' => $invoice->document_number,
        ]))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function convertToPurchaseOrder(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        if ($salesOrder->status !== SalesOrder::STATUS_CONFIRMED) {
            flash(trans('sales-purchase-orders::general.messages.must_be_confirmed'))->warning();
            return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
        }

        $purchaseOrder = PurchaseOrder::create([
            'company_id' => company_id(),
            'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
            'document_number' => OrderSetting::getForCompany(company_id())->generatePurchaseOrderNumber(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'issued_at' => now(),
            'due_at' => $salesOrder->due_at,
            'amount' => 0,
            'currency_code' => $salesOrder->currency_code,
            'currency_rate' => $salesOrder->currency_rate,
            'category_id' => $salesOrder->category_id,
            'contact_id' => $salesOrder->contact_id,
            'contact_name' => $salesOrder->contact_name,
            'contact_email' => $salesOrder->contact_email,
            'contact_tax_number' => $salesOrder->contact_tax_number,
            'contact_phone' => $salesOrder->contact_phone,
            'contact_address' => $salesOrder->contact_address,
            'contact_country' => $salesOrder->contact_country,
            'contact_state' => $salesOrder->contact_state,
            'contact_zip_code' => $salesOrder->contact_zip_code,
            'contact_city' => $salesOrder->contact_city,
            'discount_type' => $salesOrder->discount_type,
            'discount_rate' => $salesOrder->discount_rate,
            'notes' => $salesOrder->notes,
            'footer' => $salesOrder->footer,
            'parent_id' => $salesOrder->id,
            'created_from' => 'sales-purchase-orders::sales-orders.convert-po',
            'created_by' => auth()->id(),
        ]);

        foreach ($salesOrder->items as $item) {
            $poItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => PurchaseOrder::PURCHASE_ORDER_TYPE,
                'document_id' => $purchaseOrder->id,
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
                        'document_id' => $purchaseOrder->id,
                        'document_item_id' => $poItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        $this->calculateTotals($purchaseOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $salesOrder->id,
            'status' => $salesOrder->status,
            'description' => trans('sales-purchase-orders::general.messages.converted_to_po', [
                'number' => $salesOrder->document_number,
                'po' => $purchaseOrder->document_number,
            ]),
        ]);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $purchaseOrder->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.po_created_from_so', [
                'number' => $purchaseOrder->document_number,
                'so' => $salesOrder->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.converted_po_success', [
            'po' => $purchaseOrder->document_number,
        ]))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $salesOrder->id);
    }

    public function duplicate(int $id): Response
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['items.taxes'])
            ->findOrFail($id);

        $settings = OrderSetting::getForCompany(company_id());

        $newSalesOrder = SalesOrder::create([
            'company_id' => company_id(),
            'type' => SalesOrder::SALES_ORDER_TYPE,
            'document_number' => $settings->generateSalesOrderNumber(),
            'status' => SalesOrder::STATUS_DRAFT,
            'issued_at' => now(),
            'due_at' => $salesOrder->due_at ? now()->addDays($salesOrder->issued_at->diffInDays($salesOrder->due_at)) : null,
            'amount' => 0,
            'currency_code' => $salesOrder->currency_code,
            'currency_rate' => $salesOrder->currency_rate,
            'category_id' => $salesOrder->category_id,
            'contact_id' => $salesOrder->contact_id,
            'contact_name' => $salesOrder->contact_name,
            'contact_email' => $salesOrder->contact_email,
            'contact_tax_number' => $salesOrder->contact_tax_number,
            'contact_phone' => $salesOrder->contact_phone,
            'contact_address' => $salesOrder->contact_address,
            'contact_country' => $salesOrder->contact_country,
            'contact_state' => $salesOrder->contact_state,
            'contact_zip_code' => $salesOrder->contact_zip_code,
            'contact_city' => $salesOrder->contact_city,
            'discount_type' => $salesOrder->discount_type,
            'discount_rate' => $salesOrder->discount_rate,
            'title' => $salesOrder->title,
            'subheading' => $salesOrder->subheading,
            'notes' => $salesOrder->notes,
            'footer' => $salesOrder->footer,
            'created_from' => 'sales-purchase-orders::sales-orders.duplicate',
            'created_by' => auth()->id(),
        ]);

        foreach ($salesOrder->items as $item) {
            $newItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => SalesOrder::SALES_ORDER_TYPE,
                'document_id' => $newSalesOrder->id,
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
                        'type' => SalesOrder::SALES_ORDER_TYPE,
                        'document_id' => $newSalesOrder->id,
                        'document_item_id' => $newItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        $this->calculateTotals($newSalesOrder);

        OrderHistory::create([
            'company_id' => company_id(),
            'document_id' => $newSalesOrder->id,
            'status' => SalesOrder::STATUS_DRAFT,
            'description' => trans('sales-purchase-orders::general.messages.duplicated', [
                'number' => $salesOrder->document_number,
            ]),
        ]);

        flash(trans('sales-purchase-orders::general.messages.duplicated_success'))->success();

        return redirect()->route('sales-purchase-orders.sales-orders.show', $newSalesOrder->id);
    }

    public function pdf(int $id)
    {
        $salesOrder = SalesOrder::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->findOrFail($id);

        $html = view('sales-purchase-orders::sales-orders.pdf', compact('salesOrder'))->render();

        $filename = $salesOrder->document_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($filename);
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function settings(Request $request): Response
    {
        $settings = OrderSetting::getForCompany(company_id());

        if ($request->isMethod('post')) {
            $request->validate([
                'so_prefix' => 'required|string|max:20',
                'so_next_number' => 'required|integer|min:1',
                'po_prefix' => 'required|string|max:20',
                'po_next_number' => 'required|integer|min:1',
                'default_terms' => 'nullable|string',
                'template' => 'required|string|max:50',
            ]);

            $settings->update([
                'so_prefix' => $request->get('so_prefix'),
                'so_next_number' => $request->get('so_next_number'),
                'po_prefix' => $request->get('po_prefix'),
                'po_next_number' => $request->get('po_next_number'),
                'default_terms' => $request->get('default_terms'),
                'template' => $request->get('template'),
            ]);

            flash(trans('messages.success.updated', ['type' => trans('sales-purchase-orders::general.settings')]))->success();

            return redirect()->route('sales-purchase-orders.settings');
        }

        return view('sales-purchase-orders::sales-orders.settings', compact('settings'));
    }

    public function report(): Response
    {
        $companyId = company_id();

        $totalOrders = SalesOrder::where('company_id', $companyId)->count();

        $byStatus = [];
        foreach (SalesOrder::STATUSES as $status) {
            $byStatus[$status] = [
                'label' => trans('sales-purchase-orders::general.so_statuses.' . $status),
                'count' => SalesOrder::where('company_id', $companyId)->where('status', $status)->count(),
            ];
        }

        $byCustomer = SalesOrder::where('company_id', $companyId)
            ->selectRaw('contact_id, contact_name, COUNT(*) as order_count, SUM(amount) as total_amount')
            ->groupBy('contact_id', 'contact_name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row) {
                return [
                    'contact_id' => $row->contact_id,
                    'name' => $row->contact_name,
                    'count' => $row->order_count,
                    'total_amount' => $row->total_amount,
                ];
            });

        $recentOrders = SalesOrder::where('company_id', $companyId)
            ->with(['contact'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalAmount = SalesOrder::where('company_id', $companyId)->sum('amount');

        return view('sales-purchase-orders::sales-orders.report', compact(
            'totalOrders', 'byStatus', 'byCustomer', 'recentOrders', 'totalAmount'
        ));
    }

    public function import(): Response
    {
        return view('sales-purchase-orders::sales-orders.import');
    }

    public function importProcess(Request $request): Response
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            flash(trans('sales-purchase-orders::general.messages.import_failed'))->error();
            return redirect()->route('sales-purchase-orders.sales-orders.import');
        }

        $settings = OrderSetting::getForCompany(company_id());
        $header = fgetcsv($handle);
        $imported = 0;
        $errors = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if (count($row) < 4) {
                $errors[] = trans('sales-purchase-orders::general.messages.import_row_invalid', ['line' => $lineNumber]);
                continue;
            }

            $data = array_combine(['name', 'quantity', 'price', 'contact_name'], array_slice($row, 0, 4));

            $contact = Contact::where('company_id', company_id())
                ->where('type', 'customer')
                ->where('name', trim($data['contact_name']))
                ->first();

            if (! $contact) {
                $errors[] = trans('sales-purchase-orders::general.messages.import_contact_not_found', [
                    'line' => $lineNumber,
                    'name' => $data['contact_name'],
                ]);
                continue;
            }

            $quantity = (float) $data['quantity'];
            $price = (float) $data['price'];

            if ($quantity <= 0 || $price <= 0) {
                $errors[] = trans('sales-purchase-orders::general.messages.import_invalid_values', ['line' => $lineNumber]);
                continue;
            }

            $salesOrder = SalesOrder::create([
                'company_id' => company_id(),
                'type' => SalesOrder::SALES_ORDER_TYPE,
                'document_number' => $settings->generateSalesOrderNumber(),
                'status' => SalesOrder::STATUS_DRAFT,
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
                'amount' => 0,
                'currency_code' => setting('default.currency'),
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
                'created_from' => 'sales-purchase-orders::sales-orders.import',
                'created_by' => auth()->id(),
            ]);

            $subtotal = $quantity * $price;

            DocumentItem::create([
                'company_id' => company_id(),
                'type' => SalesOrder::SALES_ORDER_TYPE,
                'document_id' => $salesOrder->id,
                'name' => trim($data['name']),
                'quantity' => $quantity,
                'price' => $price,
                'total' => $subtotal,
                'tax' => 0,
                'discount_rate' => 0,
                'discount_type' => 'percentage',
            ]);

            $this->calculateTotals($salesOrder);

            OrderHistory::create([
                'company_id' => company_id(),
                'document_id' => $salesOrder->id,
                'status' => SalesOrder::STATUS_DRAFT,
                'description' => trans('sales-purchase-orders::general.messages.imported', ['number' => $salesOrder->document_number]),
            ]);

            $imported++;
        }

        fclose($handle);

        if ($imported > 0) {
            flash(trans('sales-purchase-orders::general.messages.import_success', ['count' => $imported]))->success();
        }

        if (! empty($errors)) {
            flash(implode('<br>', $errors))->error();
        }

        return redirect()->route('sales-purchase-orders.sales-orders.index');
    }

    public function export(): \Illuminate\Http\Response
    {
        $salesOrders = SalesOrder::where('company_id', company_id())
            ->with(['contact', 'items', 'totals'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'sales-orders-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($salesOrders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Document Number',
                'Status',
                'Customer Name',
                'Customer Email',
                'Order Date',
                'Delivery Date',
                'Currency',
                'Sub Total',
                'Discount',
                'Tax',
                'Total',
                'Items',
                'Notes',
                'Created At',
            ]);

            foreach ($salesOrders as $order) {
                $itemNames = $order->items->pluck('name')->implode('; ');
                $subTotal = $order->totals->where('code', 'sub_total')->first();
                $discount = $order->totals->where('code', 'discount')->first();
                $tax = $order->totals->where('code', 'tax')->first();

                fputcsv($handle, [
                    $order->document_number,
                    $order->status,
                    $order->contact_name,
                    $order->contact_email,
                    $order->issued_at ? $order->issued_at->format('Y-m-d') : '',
                    $order->due_at ? $order->due_at->format('Y-m-d') : '',
                    $order->currency_code,
                    $subTotal ? $subTotal->amount : 0,
                    $discount ? $discount->amount : 0,
                    $tax ? $tax->amount : 0,
                    $order->amount,
                    $itemNames,
                    $order->notes,
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function saveItems(SalesOrder|PurchaseOrder $document, array $items): void
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
                $tax = Tax::where('company_id', company_id())->find($taxId);
                if ($tax) {
                    $taxAmount = $subtotal * ($tax->rate / 100);
                }
            }

            $documentItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => $document->type,
                'document_id' => $document->id,
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
                    'type' => $document->type,
                    'document_id' => $document->id,
                    'document_item_id' => $documentItem->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name,
                    'amount' => $taxAmount,
                ]);
            }
        }
    }

    protected function calculateTotals(SalesOrder|PurchaseOrder $document): void
    {
        $document->totals()->delete();

        $document->load('items.taxes');

        $subTotal = $document->items->sum('total');
        $taxTotal = $document->items->sum('tax');
        $discountTotal = 0;

        if ($document->discount_rate > 0) {
            if ($document->discount_type === 'percentage') {
                $discountTotal = $subTotal * ($document->discount_rate / 100);
            } else {
                $discountTotal = $document->discount_rate;
            }
        }

        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => $document->type,
            'document_id' => $document->id,
            'code' => 'sub_total',
            'name' => 'sales-purchase-orders::general.sub_total',
            'amount' => $subTotal,
            'sort_order' => 1,
        ]);

        if ($discountTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => $document->type,
                'document_id' => $document->id,
                'code' => 'discount',
                'name' => 'sales-purchase-orders::general.discount',
                'amount' => $discountTotal,
                'sort_order' => 2,
            ]);
        }

        if ($taxTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => $document->type,
                'document_id' => $document->id,
                'code' => 'tax',
                'name' => 'sales-purchase-orders::general.tax',
                'amount' => $taxTotal,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => $document->type,
            'document_id' => $document->id,
            'code' => 'total',
            'name' => 'sales-purchase-orders::general.total',
            'amount' => $grandTotal,
            'sort_order' => 4,
        ]);

        $document->update(['amount' => $grandTotal]);
    }

    protected function getNextInvoiceNumber(): string
    {
        $prefix = setting('invoice.number_prefix', 'INV-');
        $next = setting('invoice.number_next', 1);

        $number = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);

        setting(['invoice.number_next' => $next + 1]);
        setting()->save();

        return $number;
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
