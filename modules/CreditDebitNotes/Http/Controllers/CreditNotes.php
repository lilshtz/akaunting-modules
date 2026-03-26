<?php

namespace Modules\CreditDebitNotes\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentItemTax;
use App\Models\Document\DocumentTotal;
use App\Models\Setting\Currency;
use App\Models\Setting\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\CreditDebitNotes\Http\Requests\CreditNoteStore;
use Modules\CreditDebitNotes\Http\Requests\CreditNoteUpdate;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\CreditNoteApplication;
use Modules\CreditDebitNotes\Models\NoteHistory;
use Modules\CreditDebitNotes\Models\NotePortalToken;
use Modules\CreditDebitNotes\Models\NoteSetting;
use Modules\CreditDebitNotes\Notifications\CreditNoteSent;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;

class CreditNotes extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = CreditNote::where('company_id', company_id())
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

        $creditNotes = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [];
        foreach (CreditNote::STATUSES as $status) {
            $statuses[$status] = trans('credit-debit-notes::general.statuses.' . $status);
        }

        $customers = Contact::where('company_id', company_id())
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->response('credit-debit-notes::credit-notes.index', compact('creditNotes', 'statuses', 'customers'));
    }

    public function create(): Response|mixed
    {
        $invoices = Document::where('company_id', company_id())
            ->where('type', Document::INVOICE_TYPE)
            ->orderBy('document_number', 'desc')
            ->get()
            ->mapWithKeys(function ($inv) {
                return [$inv->id => $inv->document_number . ' - ' . $inv->contact_name . ' (' . money($inv->amount, $inv->currency_code) . ')'];
            });

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $taxes = Tax::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->rate . '%)'];
            })
            ->prepend(trans('general.none'), '');

        $defaultCurrency = Currency::where('company_id', company_id())
            ->where('code', setting('default.currency'))
            ->first();

        return view('credit-debit-notes::credit-notes.create', compact(
            'invoices', 'currencies', 'taxes', 'defaultCurrency'
        ));
    }

    public function store(CreditNoteStore $request): Response|mixed
    {
        $settings = NoteSetting::getForCompany(company_id());
        $linkedInvoice = Document::findOrFail($request->get('parent_id'));

        $creditNote = CreditNote::create([
            'company_id' => company_id(),
            'type' => CreditNote::NOTE_TYPE,
            'document_number' => $settings->generateCreditNumber(),
            'status' => CreditNote::STATUS_DRAFT,
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'amount' => 0,
            'currency_code' => $request->get('currency_code', $linkedInvoice->currency_code),
            'currency_rate' => $request->get('currency_rate', 1),
            'category_id' => $linkedInvoice->category_id,
            'contact_id' => $linkedInvoice->contact_id,
            'contact_name' => $linkedInvoice->contact_name,
            'contact_email' => $linkedInvoice->contact_email,
            'contact_tax_number' => $linkedInvoice->contact_tax_number,
            'contact_phone' => $linkedInvoice->contact_phone,
            'contact_address' => $linkedInvoice->contact_address,
            'contact_country' => $linkedInvoice->contact_country,
            'contact_state' => $linkedInvoice->contact_state,
            'contact_zip_code' => $linkedInvoice->contact_zip_code,
            'contact_city' => $linkedInvoice->contact_city,
            'parent_id' => $linkedInvoice->id,
            'notes' => $request->get('notes'),
            'footer' => $request->get('footer'),
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
            'created_from' => 'credit-debit-notes::credit-notes',
            'created_by' => auth()->id(),
        ]);

        $this->saveItems($creditNote, $request->get('items'));
        $this->calculateTotals($creditNote);

        $this->postCreditNoteJournal($creditNote);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => CreditNote::STATUS_DRAFT,
            'description' => trans('credit-debit-notes::general.messages.cn_created', ['number' => $creditNote->document_number]),
        ]);

        flash(trans('messages.success.added', ['type' => trans('credit-debit-notes::general.credit_note')]))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function show(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'histories', 'portalToken', 'linkedInvoice', 'applications.invoice'])
            ->findOrFail($id);

        $openInvoices = Document::where('company_id', company_id())
            ->where('type', Document::INVOICE_TYPE)
            ->whereIn('status', ['sent', 'partial', 'viewed'])
            ->where('id', '!=', $creditNote->parent_id)
            ->orderBy('document_number', 'desc')
            ->get()
            ->mapWithKeys(function ($inv) {
                return [$inv->id => $inv->document_number . ' - ' . $inv->contact_name . ' (' . money($inv->amount, $inv->currency_code) . ')'];
            });

        return view('credit-debit-notes::credit-notes.show', compact('creditNote', 'openInvoices'));
    }

    public function edit(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes'])
            ->findOrFail($id);

        if (! $creditNote->isEditable()) {
            flash(trans('credit-debit-notes::general.messages.not_editable'))->warning();
            return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
        }

        $invoices = Document::where('company_id', company_id())
            ->where('type', Document::INVOICE_TYPE)
            ->orderBy('document_number', 'desc')
            ->get()
            ->mapWithKeys(function ($inv) {
                return [$inv->id => $inv->document_number . ' - ' . $inv->contact_name . ' (' . money($inv->amount, $inv->currency_code) . ')'];
            });

        $currencies = Currency::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'code');

        $taxes = Tax::where('company_id', company_id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($tax) {
                return [$tax->id => $tax->name . ' (' . $tax->rate . '%)'];
            })
            ->prepend(trans('general.none'), '');

        return view('credit-debit-notes::credit-notes.edit', compact('creditNote', 'invoices', 'currencies', 'taxes'));
    }

    public function update(int $id, CreditNoteUpdate $request): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())->findOrFail($id);

        if (! $creditNote->isEditable()) {
            flash(trans('credit-debit-notes::general.messages.not_editable'))->warning();
            return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
        }

        $linkedInvoice = Document::findOrFail($request->get('parent_id'));

        $creditNote->update([
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'currency_code' => $request->get('currency_code', $creditNote->currency_code),
            'currency_rate' => $request->get('currency_rate', 1),
            'parent_id' => $linkedInvoice->id,
            'contact_id' => $linkedInvoice->contact_id,
            'contact_name' => $linkedInvoice->contact_name,
            'contact_email' => $linkedInvoice->contact_email,
            'contact_tax_number' => $linkedInvoice->contact_tax_number,
            'contact_phone' => $linkedInvoice->contact_phone,
            'contact_address' => $linkedInvoice->contact_address,
            'contact_country' => $linkedInvoice->contact_country,
            'contact_state' => $linkedInvoice->contact_state,
            'contact_zip_code' => $linkedInvoice->contact_zip_code,
            'contact_city' => $linkedInvoice->contact_city,
            'notes' => $request->get('notes'),
            'footer' => $request->get('footer'),
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
        ]);

        $creditNote->items()->delete();
        $creditNote->item_taxes()->delete();
        $this->saveItems($creditNote, $request->get('items'));
        $this->calculateTotals($creditNote);

        $this->deletePreviousJournals($creditNote);
        $this->postCreditNoteJournal($creditNote);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => CreditNote::STATUS_DRAFT,
            'description' => trans('credit-debit-notes::general.messages.cn_updated', ['number' => $creditNote->document_number]),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('credit-debit-notes::general.credit_note')]))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())->findOrFail($id);

        if (! $creditNote->isDeletable()) {
            flash(trans('credit-debit-notes::general.messages.not_deletable'))->warning();
            return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
        }

        $this->deletePreviousJournals($creditNote);

        $creditNote->items()->delete();
        $creditNote->item_taxes()->delete();
        $creditNote->histories()->delete();
        $creditNote->portalToken()->delete();
        $creditNote->totals()->delete();
        $creditNote->delete();

        flash(trans('messages.success.deleted', ['type' => trans('credit-debit-notes::general.credit_note')]))->success();

        return redirect()->route('credit-debit-notes.credit-notes.index');
    }

    public function send(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $portalToken = NotePortalToken::generateForDocument(company_id(), $creditNote->id);

        $creditNote->update(['status' => CreditNote::STATUS_SENT]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => CreditNote::STATUS_SENT,
            'notify' => '1',
            'description' => trans('credit-debit-notes::general.messages.cn_sent', [
                'number' => $creditNote->document_number,
                'email' => $creditNote->contact_email,
            ]),
        ]);

        if ($creditNote->contact_email) {
            try {
                $creditNote->contact->notify(new CreditNoteSent($creditNote, $portalToken));
            } catch (\Exception $e) {
                report($e);
            }
        }

        flash(trans('credit-debit-notes::general.messages.sent_success'))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function markOpen(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())->findOrFail($id);

        $creditNote->update(['status' => CreditNote::STATUS_OPEN]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => CreditNote::STATUS_OPEN,
            'description' => trans('credit-debit-notes::general.messages.marked_open'),
        ]);

        flash(trans('credit-debit-notes::general.messages.marked_open'))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function cancel(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())->findOrFail($id);

        $creditNote->update(['status' => CreditNote::STATUS_CANCELLED]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => CreditNote::STATUS_CANCELLED,
            'description' => trans('credit-debit-notes::general.messages.cancelled'),
        ]);

        flash(trans('credit-debit-notes::general.messages.cancelled'))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function applyCredit(Request $request, int $id): Response|mixed
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:documents,id',
            'amount' => 'required|numeric|gt:0',
        ]);

        $creditNote = CreditNote::where('company_id', company_id())
            ->with('applications')
            ->findOrFail($id);

        $invoice = Document::where('company_id', company_id())->findOrFail($request->get('invoice_id'));

        $amount = (float) $request->get('amount');

        if ($amount > $creditNote->available_amount) {
            flash(trans('credit-debit-notes::general.messages.exceeds_available'))->error();
            return redirect()->back();
        }

        CreditNoteApplication::create([
            'company_id' => company_id(),
            'credit_note_id' => $creditNote->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'date' => now(),
        ]);

        $creditNote->load('applications');
        $remaining = $creditNote->available_amount;

        if ($remaining <= 0) {
            $creditNote->update(['status' => CreditNote::STATUS_CLOSED]);
        } elseif ($creditNote->status !== CreditNote::STATUS_PARTIAL) {
            $creditNote->update(['status' => CreditNote::STATUS_PARTIAL]);
        }

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => $creditNote->status,
            'description' => trans('credit-debit-notes::general.messages.credit_applied', [
                'amount' => money($amount, $creditNote->currency_code),
                'invoice' => $invoice->document_number,
            ]),
        ]);

        flash(trans('credit-debit-notes::general.messages.credit_applied_success'))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function refund(Request $request, int $id): Response|mixed
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $creditNote = CreditNote::where('company_id', company_id())
            ->with('applications')
            ->findOrFail($id);

        $amount = (float) $request->get('amount');

        if ($amount > $creditNote->available_amount) {
            flash(trans('credit-debit-notes::general.messages.exceeds_available'))->error();
            return redirect()->back();
        }

        CreditNoteApplication::create([
            'company_id' => company_id(),
            'credit_note_id' => $creditNote->id,
            'invoice_id' => $creditNote->parent_id,
            'amount' => $amount,
            'date' => now(),
        ]);

        $creditNote->load('applications');

        if ($creditNote->available_amount <= 0) {
            $creditNote->update(['status' => CreditNote::STATUS_CLOSED]);
        } else {
            $creditNote->update(['status' => CreditNote::STATUS_PARTIAL]);
        }

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => $creditNote->status,
            'description' => trans('credit-debit-notes::general.messages.refund_recorded', [
                'amount' => money($amount, $creditNote->currency_code),
            ]),
        ]);

        flash(trans('credit-debit-notes::general.messages.refund_success'))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function convertToInvoice(int $id): Response|mixed
    {
        $creditNote = CreditNote::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        $invoice = Document::create([
            'company_id' => company_id(),
            'type' => Document::INVOICE_TYPE,
            'document_number' => $this->getNextInvoiceNumber(),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'amount' => $creditNote->amount,
            'currency_code' => $creditNote->currency_code,
            'currency_rate' => $creditNote->currency_rate,
            'category_id' => $creditNote->category_id,
            'contact_id' => $creditNote->contact_id,
            'contact_name' => $creditNote->contact_name,
            'contact_email' => $creditNote->contact_email,
            'contact_tax_number' => $creditNote->contact_tax_number,
            'contact_phone' => $creditNote->contact_phone,
            'contact_address' => $creditNote->contact_address,
            'contact_country' => $creditNote->contact_country,
            'contact_state' => $creditNote->contact_state,
            'contact_zip_code' => $creditNote->contact_zip_code,
            'contact_city' => $creditNote->contact_city,
            'discount_type' => $creditNote->discount_type,
            'discount_rate' => $creditNote->discount_rate,
            'notes' => $creditNote->notes,
            'footer' => $creditNote->footer,
            'parent_id' => $creditNote->id,
            'created_from' => 'credit-debit-notes::credit-notes.convert',
            'created_by' => auth()->id(),
        ]);

        foreach ($creditNote->items as $item) {
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

        foreach ($creditNote->totals as $total) {
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

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $creditNote->id,
            'status' => $creditNote->status,
            'description' => trans('credit-debit-notes::general.messages.converted_to_invoice', [
                'invoice' => $invoice->document_number,
            ]),
        ]);

        flash(trans('credit-debit-notes::general.messages.converted_success', [
            'invoice' => $invoice->document_number,
        ]))->success();

        return redirect()->route('credit-debit-notes.credit-notes.show', $creditNote->id);
    }

    public function pdf(int $id)
    {
        $creditNote = CreditNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->findOrFail($id);

        $html = view('credit-debit-notes::credit-notes.pdf', compact('creditNote'))->render();

        $filename = $creditNote->document_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($filename);
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    protected function saveItems(CreditNote $creditNote, array $items): void
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
                'type' => CreditNote::NOTE_TYPE,
                'document_id' => $creditNote->id,
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
                    'type' => CreditNote::NOTE_TYPE,
                    'document_id' => $creditNote->id,
                    'document_item_id' => $documentItem->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name,
                    'amount' => $taxAmount,
                ]);
            }
        }
    }

    protected function calculateTotals(CreditNote $creditNote): void
    {
        $creditNote->totals()->delete();
        $creditNote->load('items.taxes');

        $subTotal = $creditNote->items->sum('total');
        $taxTotal = $creditNote->items->sum('tax');
        $discountTotal = 0;

        if ($creditNote->discount_rate > 0) {
            if ($creditNote->discount_type === 'percentage') {
                $discountTotal = $subTotal * ($creditNote->discount_rate / 100);
            } else {
                $discountTotal = $creditNote->discount_rate;
            }
        }

        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => CreditNote::NOTE_TYPE,
            'document_id' => $creditNote->id,
            'code' => 'sub_total',
            'name' => 'credit-debit-notes::general.sub_total',
            'amount' => $subTotal,
            'sort_order' => 1,
        ]);

        if ($discountTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => CreditNote::NOTE_TYPE,
                'document_id' => $creditNote->id,
                'code' => 'discount',
                'name' => 'credit-debit-notes::general.discount',
                'amount' => $discountTotal,
                'sort_order' => 2,
            ]);
        }

        if ($taxTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => CreditNote::NOTE_TYPE,
                'document_id' => $creditNote->id,
                'code' => 'tax',
                'name' => 'credit-debit-notes::general.tax',
                'amount' => $taxTotal,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => CreditNote::NOTE_TYPE,
            'document_id' => $creditNote->id,
            'code' => 'total',
            'name' => 'credit-debit-notes::general.total',
            'amount' => $grandTotal,
            'sort_order' => 4,
        ]);

        $creditNote->update(['amount' => $grandTotal]);
    }

    protected function postCreditNoteJournal(CreditNote $creditNote): void
    {
        $companyId = $creditNote->company_id;

        $arDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'accounts_receivable')->first();
        $revenueDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'sales_revenue')->first();

        if (! $arDefault || ! $revenueDefault) {
            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $creditNote->issued_at ?? $creditNote->created_at,
            'reference' => 'CN-' . $creditNote->document_number,
            'description' => 'Credit note ' . $creditNote->document_number . ' for invoice ' . ($creditNote->linkedInvoice?->document_number ?? 'N/A'),
            'basis' => 'accrual',
            'status' => 'posted',
            'documentable_type' => get_class($creditNote),
            'documentable_id' => $creditNote->id,
            'created_by' => auth()->id(),
        ]);

        // DR Revenue / CR Accounts Receivable
        $journal->lines()->create([
            'account_id' => $revenueDefault->account_id,
            'debit' => $creditNote->amount,
            'credit' => 0,
            'description' => 'Sales return - ' . $creditNote->document_number,
        ]);

        $journal->lines()->create([
            'account_id' => $arDefault->account_id,
            'debit' => 0,
            'credit' => $creditNote->amount,
            'description' => 'Customer credit - ' . $creditNote->document_number,
        ]);
    }

    protected function deletePreviousJournals($document): void
    {
        Journal::where('company_id', $document->company_id)
            ->where('documentable_type', get_class($document))
            ->where('documentable_id', $document->id)
            ->each(function ($journal) {
                $journal->lines()->delete();
                $journal->forceDelete();
            });
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
