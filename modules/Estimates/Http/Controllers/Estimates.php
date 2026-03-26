<?php

namespace Modules\Estimates\Http\Controllers;

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
use Modules\Estimates\Http\Requests\EstimateStore;
use Modules\Estimates\Http\Requests\EstimateUpdate;
use Modules\Estimates\Models\Estimate;
use Modules\Estimates\Models\EstimateHistory;
use Modules\Estimates\Models\EstimatePortalToken;
use Modules\Estimates\Models\EstimateSetting;
use Modules\Estimates\Notifications\EstimateSent;
use Modules\Estimates\Notifications\EstimateStatusChanged;

class Estimates extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = Estimate::where('company_id', company_id())
            ->with(['contact', 'items', 'totals']);

        if ($request->filled('status')) {
            $query->status($request->get('status'));
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

        // Check and mark expired estimates
        $now = now();
        Estimate::where('company_id', company_id())
            ->whereNotIn('status', [
                Estimate::STATUS_APPROVED,
                Estimate::STATUS_CONVERTED,
                Estimate::STATUS_EXPIRED,
            ])
            ->where('due_at', '<', $now)
            ->whereNotNull('due_at')
            ->each(function ($estimate) {
                $estimate->checkExpiry();
            });

        $estimates = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [];
        foreach (Estimate::STATUSES as $status) {
            $statuses[$status] = trans('estimates::general.statuses.' . $status);
        }

        $customers = Contact::where('company_id', company_id())
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->response('estimates::estimates.index', compact('estimates', 'statuses', 'customers'));
    }

    public function create(): Response|mixed
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

        $settings = EstimateSetting::getForCompany(company_id());

        $defaultCurrency = Currency::where('company_id', company_id())
            ->where('code', setting('default.currency'))
            ->first();

        return view('estimates::estimates.create', compact(
            'customers', 'currencies', 'categories', 'taxes', 'settings', 'defaultCurrency'
        ));
    }

    public function store(EstimateStore $request): Response|mixed
    {
        $settings = EstimateSetting::getForCompany(company_id());

        $contact = Contact::findOrFail($request->get('contact_id'));

        $estimate = Estimate::create([
            'company_id' => company_id(),
            'type' => Estimate::ESTIMATE_TYPE,
            'document_number' => $settings->generateNumber(),
            'status' => Estimate::STATUS_DRAFT,
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
            'created_from' => 'estimates::estimates',
            'created_by' => auth()->id(),
        ]);

        $this->saveItems($estimate, $request->get('items'));
        $this->calculateTotals($estimate);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_DRAFT,
            'description' => trans('estimates::general.messages.created', ['number' => $estimate->document_number]),
        ]);

        flash(trans('messages.success.added', ['type' => trans('estimates::general.estimate')]))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function show(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'estimateHistories', 'portalToken', 'convertedInvoice'])
            ->findOrFail($id);

        $estimate->checkExpiry();

        return view('estimates::estimates.show', compact('estimate'));
    }

    public function edit(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with(['contact', 'items.taxes'])
            ->findOrFail($id);

        if (! $estimate->isEditable()) {
            flash(trans('estimates::general.messages.not_editable'))->warning();
            return redirect()->route('estimates.estimates.show', $estimate->id);
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

        return view('estimates::estimates.edit', compact('estimate', 'customers', 'currencies', 'categories', 'taxes'));
    }

    public function update(int $id, EstimateUpdate $request): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())->findOrFail($id);

        if (! $estimate->isEditable()) {
            flash(trans('estimates::general.messages.not_editable'))->warning();
            return redirect()->route('estimates.estimates.show', $estimate->id);
        }

        $contact = Contact::findOrFail($request->get('contact_id'));

        $estimate->update([
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'currency_code' => $request->get('currency_code', $estimate->currency_code),
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
            'status' => Estimate::STATUS_DRAFT,
        ]);

        $estimate->items()->delete();
        $estimate->item_taxes()->delete();
        $this->saveItems($estimate, $request->get('items'));
        $this->calculateTotals($estimate);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_DRAFT,
            'description' => trans('estimates::general.messages.updated', ['number' => $estimate->document_number]),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('estimates::general.estimate')]))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())->findOrFail($id);

        if (! $estimate->isDeletable()) {
            flash(trans('estimates::general.messages.not_deletable'))->warning();
            return redirect()->route('estimates.estimates.show', $estimate->id);
        }

        $estimate->items()->delete();
        $estimate->item_taxes()->delete();
        $estimate->estimateHistories()->delete();
        $estimate->portalToken()->delete();
        $estimate->totals()->delete();
        $estimate->delete();

        flash(trans('messages.success.deleted', ['type' => trans('estimates::general.estimate')]))->success();

        return redirect()->route('estimates.estimates.index');
    }

    public function send(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $portalToken = EstimatePortalToken::generateForDocument(company_id(), $estimate->id);

        $estimate->update(['status' => Estimate::STATUS_SENT]);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_SENT,
            'notify' => '1',
            'description' => trans('estimates::general.messages.sent', [
                'number' => $estimate->document_number,
                'email' => $estimate->contact_email,
            ]),
        ]);

        if ($estimate->contact_email) {
            try {
                $estimate->contact->notify(new EstimateSent($estimate, $portalToken));
            } catch (\Exception $e) {
                report($e);
            }
        }

        flash(trans('estimates::general.messages.sent_success'))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function approve(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())->findOrFail($id);

        $estimate->update(['status' => Estimate::STATUS_APPROVED]);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_APPROVED,
            'description' => trans('estimates::general.messages.approved_manually'),
        ]);

        flash(trans('estimates::general.messages.approved'))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function refuse(Request $request, int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())->findOrFail($id);

        $estimate->update(['status' => Estimate::STATUS_REFUSED]);

        $reason = $request->get('reason', '');

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_REFUSED,
            'description' => trans('estimates::general.messages.refused_manually') .
                ($reason ? ': ' . $reason : ''),
        ]);

        flash(trans('estimates::general.messages.refused'))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function convert(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        if ($estimate->status !== Estimate::STATUS_APPROVED) {
            flash(trans('estimates::general.messages.must_be_approved'))->warning();
            return redirect()->route('estimates.estimates.show', $estimate->id);
        }

        $invoice = Document::create([
            'company_id' => company_id(),
            'type' => Document::INVOICE_TYPE,
            'document_number' => $this->getNextInvoiceNumber(),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'amount' => $estimate->amount,
            'currency_code' => $estimate->currency_code,
            'currency_rate' => $estimate->currency_rate,
            'category_id' => $estimate->category_id,
            'contact_id' => $estimate->contact_id,
            'contact_name' => $estimate->contact_name,
            'contact_email' => $estimate->contact_email,
            'contact_tax_number' => $estimate->contact_tax_number,
            'contact_phone' => $estimate->contact_phone,
            'contact_address' => $estimate->contact_address,
            'contact_country' => $estimate->contact_country,
            'contact_state' => $estimate->contact_state,
            'contact_zip_code' => $estimate->contact_zip_code,
            'contact_city' => $estimate->contact_city,
            'discount_type' => $estimate->discount_type,
            'discount_rate' => $estimate->discount_rate,
            'notes' => $estimate->notes,
            'footer' => $estimate->footer,
            'parent_id' => $estimate->id,
            'created_from' => 'estimates::estimates.convert',
            'created_by' => auth()->id(),
        ]);

        foreach ($estimate->items as $item) {
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

        foreach ($estimate->totals as $total) {
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

        $estimate->update(['status' => Estimate::STATUS_CONVERTED]);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $estimate->id,
            'status' => Estimate::STATUS_CONVERTED,
            'description' => trans('estimates::general.messages.converted', [
                'number' => $estimate->document_number,
                'invoice' => $invoice->document_number,
            ]),
        ]);

        flash(trans('estimates::general.messages.converted_success', [
            'invoice' => $invoice->document_number,
        ]))->success();

        return redirect()->route('estimates.estimates.show', $estimate->id);
    }

    public function duplicate(int $id): Response|mixed
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with(['items.taxes'])
            ->findOrFail($id);

        $settings = EstimateSetting::getForCompany(company_id());

        $newEstimate = Estimate::create([
            'company_id' => company_id(),
            'type' => Estimate::ESTIMATE_TYPE,
            'document_number' => $settings->generateNumber(),
            'status' => Estimate::STATUS_DRAFT,
            'issued_at' => now(),
            'due_at' => $estimate->due_at ? now()->addDays($estimate->issued_at->diffInDays($estimate->due_at)) : null,
            'amount' => 0,
            'currency_code' => $estimate->currency_code,
            'currency_rate' => $estimate->currency_rate,
            'category_id' => $estimate->category_id,
            'contact_id' => $estimate->contact_id,
            'contact_name' => $estimate->contact_name,
            'contact_email' => $estimate->contact_email,
            'contact_tax_number' => $estimate->contact_tax_number,
            'contact_phone' => $estimate->contact_phone,
            'contact_address' => $estimate->contact_address,
            'contact_country' => $estimate->contact_country,
            'contact_state' => $estimate->contact_state,
            'contact_zip_code' => $estimate->contact_zip_code,
            'contact_city' => $estimate->contact_city,
            'discount_type' => $estimate->discount_type,
            'discount_rate' => $estimate->discount_rate,
            'title' => $estimate->title,
            'subheading' => $estimate->subheading,
            'notes' => $estimate->notes,
            'footer' => $estimate->footer,
            'created_from' => 'estimates::estimates.duplicate',
            'created_by' => auth()->id(),
        ]);

        foreach ($estimate->items as $item) {
            $newItem = DocumentItem::create([
                'company_id' => company_id(),
                'type' => Estimate::ESTIMATE_TYPE,
                'document_id' => $newEstimate->id,
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
                        'type' => Estimate::ESTIMATE_TYPE,
                        'document_id' => $newEstimate->id,
                        'document_item_id' => $newItem->id,
                        'tax_id' => $tax->tax_id,
                        'name' => $tax->name,
                        'amount' => $tax->amount,
                    ]);
                }
            }
        }

        $this->calculateTotals($newEstimate);

        EstimateHistory::create([
            'company_id' => company_id(),
            'document_id' => $newEstimate->id,
            'status' => Estimate::STATUS_DRAFT,
            'description' => trans('estimates::general.messages.duplicated', [
                'number' => $estimate->document_number,
            ]),
        ]);

        flash(trans('estimates::general.messages.duplicated_success'))->success();

        return redirect()->route('estimates.estimates.show', $newEstimate->id);
    }

    public function pdf(int $id)
    {
        $estimate = Estimate::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->findOrFail($id);

        $html = view('estimates::estimates.pdf', compact('estimate'))->render();

        $filename = $estimate->document_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($filename);
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function settings(Request $request): Response|mixed
    {
        $settings = EstimateSetting::getForCompany(company_id());

        if ($request->isMethod('post')) {
            $request->validate([
                'prefix' => 'required|string|max:20',
                'next_number' => 'required|integer|min:1',
                'default_terms' => 'nullable|string',
                'template' => 'required|string|max:50',
                'approval_required' => 'nullable|boolean',
            ]);

            $settings->update([
                'prefix' => $request->get('prefix'),
                'next_number' => $request->get('next_number'),
                'default_terms' => $request->get('default_terms'),
                'template' => $request->get('template'),
                'approval_required' => $request->boolean('approval_required'),
            ]);

            flash(trans('messages.success.updated', ['type' => trans('estimates::general.settings')]))->success();

            return redirect()->route('estimates.settings');
        }

        return view('estimates::estimates.settings', compact('settings'));
    }

    protected function saveItems(Estimate $estimate, array $items): void
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
                'type' => Estimate::ESTIMATE_TYPE,
                'document_id' => $estimate->id,
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
                    'type' => Estimate::ESTIMATE_TYPE,
                    'document_id' => $estimate->id,
                    'document_item_id' => $documentItem->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name,
                    'amount' => $taxAmount,
                ]);
            }
        }
    }

    protected function calculateTotals(Estimate $estimate): void
    {
        $estimate->totals()->delete();

        $estimate->load('items.taxes');

        $subTotal = $estimate->items->sum('total');
        $taxTotal = $estimate->items->sum('tax');
        $discountTotal = 0;

        if ($estimate->discount_rate > 0) {
            if ($estimate->discount_type === 'percentage') {
                $discountTotal = $subTotal * ($estimate->discount_rate / 100);
            } else {
                $discountTotal = $estimate->discount_rate;
            }
        }

        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => Estimate::ESTIMATE_TYPE,
            'document_id' => $estimate->id,
            'code' => 'sub_total',
            'name' => 'estimates::general.sub_total',
            'amount' => $subTotal,
            'sort_order' => 1,
        ]);

        if ($discountTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => Estimate::ESTIMATE_TYPE,
                'document_id' => $estimate->id,
                'code' => 'discount',
                'name' => 'estimates::general.discount',
                'amount' => $discountTotal,
                'sort_order' => 2,
            ]);
        }

        if ($taxTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => Estimate::ESTIMATE_TYPE,
                'document_id' => $estimate->id,
                'code' => 'tax',
                'name' => 'estimates::general.tax',
                'amount' => $taxTotal,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => Estimate::ESTIMATE_TYPE,
            'document_id' => $estimate->id,
            'code' => 'total',
            'name' => 'estimates::general.total',
            'amount' => $grandTotal,
            'sort_order' => 4,
        ]);

        $estimate->update(['amount' => $grandTotal]);
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
}
