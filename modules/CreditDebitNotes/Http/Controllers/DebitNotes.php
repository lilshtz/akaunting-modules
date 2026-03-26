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
use Modules\CreditDebitNotes\Http\Requests\DebitNoteStore;
use Modules\CreditDebitNotes\Http\Requests\DebitNoteUpdate;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\CreditDebitNotes\Models\NoteHistory;
use Modules\CreditDebitNotes\Models\NotePortalToken;
use Modules\CreditDebitNotes\Models\NoteSetting;
use Modules\CreditDebitNotes\Notifications\DebitNoteSent;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;

class DebitNotes extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = DebitNote::where('company_id', company_id())
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

        $debitNotes = $query->orderBy('created_at', 'desc')->paginate(25);

        $statuses = [];
        foreach (DebitNote::STATUSES as $status) {
            $statuses[$status] = trans('credit-debit-notes::general.statuses.' . $status);
        }

        $vendors = Contact::where('company_id', company_id())
            ->where('type', 'vendor')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $this->response('credit-debit-notes::debit-notes.index', compact('debitNotes', 'statuses', 'vendors'));
    }

    public function create(): Response|mixed
    {
        $bills = Document::where('company_id', company_id())
            ->where('type', Document::BILL_TYPE)
            ->orderBy('document_number', 'desc')
            ->get()
            ->mapWithKeys(function ($bill) {
                return [$bill->id => $bill->document_number . ' - ' . $bill->contact_name . ' (' . money($bill->amount, $bill->currency_code) . ')'];
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

        return view('credit-debit-notes::debit-notes.create', compact(
            'bills', 'currencies', 'taxes', 'defaultCurrency'
        ));
    }

    public function store(DebitNoteStore $request): Response|mixed
    {
        $settings = NoteSetting::getForCompany(company_id());
        $linkedBill = Document::findOrFail($request->get('parent_id'));

        $debitNote = DebitNote::create([
            'company_id' => company_id(),
            'type' => DebitNote::NOTE_TYPE,
            'document_number' => $settings->generateDebitNumber(),
            'status' => DebitNote::STATUS_DRAFT,
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'amount' => 0,
            'currency_code' => $request->get('currency_code', $linkedBill->currency_code),
            'currency_rate' => $request->get('currency_rate', 1),
            'category_id' => $linkedBill->category_id,
            'contact_id' => $linkedBill->contact_id,
            'contact_name' => $linkedBill->contact_name,
            'contact_email' => $linkedBill->contact_email,
            'contact_tax_number' => $linkedBill->contact_tax_number,
            'contact_phone' => $linkedBill->contact_phone,
            'contact_address' => $linkedBill->contact_address,
            'contact_country' => $linkedBill->contact_country,
            'contact_state' => $linkedBill->contact_state,
            'contact_zip_code' => $linkedBill->contact_zip_code,
            'contact_city' => $linkedBill->contact_city,
            'parent_id' => $linkedBill->id,
            'notes' => $request->get('notes'),
            'footer' => $request->get('footer'),
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
            'created_from' => 'credit-debit-notes::debit-notes',
            'created_by' => auth()->id(),
        ]);

        $this->saveItems($debitNote, $request->get('items'));
        $this->calculateTotals($debitNote);

        $this->postDebitNoteJournal($debitNote);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => DebitNote::STATUS_DRAFT,
            'description' => trans('credit-debit-notes::general.messages.dn_created', ['number' => $debitNote->document_number]),
        ]);

        flash(trans('messages.success.added', ['type' => trans('credit-debit-notes::general.debit_note')]))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function show(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'histories', 'portalToken', 'linkedBill'])
            ->findOrFail($id);

        return view('credit-debit-notes::debit-notes.show', compact('debitNote'));
    }

    public function edit(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes'])
            ->findOrFail($id);

        if (! $debitNote->isEditable()) {
            flash(trans('credit-debit-notes::general.messages.not_editable'))->warning();
            return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
        }

        $bills = Document::where('company_id', company_id())
            ->where('type', Document::BILL_TYPE)
            ->orderBy('document_number', 'desc')
            ->get()
            ->mapWithKeys(function ($bill) {
                return [$bill->id => $bill->document_number . ' - ' . $bill->contact_name . ' (' . money($bill->amount, $bill->currency_code) . ')'];
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

        return view('credit-debit-notes::debit-notes.edit', compact('debitNote', 'bills', 'currencies', 'taxes'));
    }

    public function update(int $id, DebitNoteUpdate $request): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())->findOrFail($id);

        if (! $debitNote->isEditable()) {
            flash(trans('credit-debit-notes::general.messages.not_editable'))->warning();
            return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
        }

        $linkedBill = Document::findOrFail($request->get('parent_id'));

        $debitNote->update([
            'issued_at' => $request->get('issued_at'),
            'due_at' => $request->get('due_at'),
            'currency_code' => $request->get('currency_code', $debitNote->currency_code),
            'currency_rate' => $request->get('currency_rate', 1),
            'parent_id' => $linkedBill->id,
            'contact_id' => $linkedBill->contact_id,
            'contact_name' => $linkedBill->contact_name,
            'contact_email' => $linkedBill->contact_email,
            'contact_tax_number' => $linkedBill->contact_tax_number,
            'contact_phone' => $linkedBill->contact_phone,
            'contact_address' => $linkedBill->contact_address,
            'contact_country' => $linkedBill->contact_country,
            'contact_state' => $linkedBill->contact_state,
            'contact_zip_code' => $linkedBill->contact_zip_code,
            'contact_city' => $linkedBill->contact_city,
            'notes' => $request->get('notes'),
            'footer' => $request->get('footer'),
            'discount_type' => $request->get('discount_type', 'percentage'),
            'discount_rate' => $request->get('discount_rate', 0),
        ]);

        $debitNote->items()->delete();
        $debitNote->item_taxes()->delete();
        $this->saveItems($debitNote, $request->get('items'));
        $this->calculateTotals($debitNote);

        $this->deletePreviousJournals($debitNote);
        $this->postDebitNoteJournal($debitNote);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => DebitNote::STATUS_DRAFT,
            'description' => trans('credit-debit-notes::general.messages.dn_updated', ['number' => $debitNote->document_number]),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('credit-debit-notes::general.debit_note')]))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())->findOrFail($id);

        if (! $debitNote->isDeletable()) {
            flash(trans('credit-debit-notes::general.messages.not_deletable'))->warning();
            return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
        }

        $this->deletePreviousJournals($debitNote);

        $debitNote->items()->delete();
        $debitNote->item_taxes()->delete();
        $debitNote->histories()->delete();
        $debitNote->portalToken()->delete();
        $debitNote->totals()->delete();
        $debitNote->delete();

        flash(trans('messages.success.deleted', ['type' => trans('credit-debit-notes::general.debit_note')]))->success();

        return redirect()->route('credit-debit-notes.debit-notes.index');
    }

    public function send(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $portalToken = NotePortalToken::generateForDocument(company_id(), $debitNote->id);

        $debitNote->update(['status' => DebitNote::STATUS_SENT]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => DebitNote::STATUS_SENT,
            'notify' => '1',
            'description' => trans('credit-debit-notes::general.messages.dn_sent', [
                'number' => $debitNote->document_number,
                'email' => $debitNote->contact_email,
            ]),
        ]);

        if ($debitNote->contact_email) {
            try {
                $debitNote->contact->notify(new DebitNoteSent($debitNote, $portalToken));
            } catch (\Exception $e) {
                report($e);
            }
        }

        flash(trans('credit-debit-notes::general.messages.sent_success'))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function markOpen(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())->findOrFail($id);

        $debitNote->update(['status' => DebitNote::STATUS_OPEN]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => DebitNote::STATUS_OPEN,
            'description' => trans('credit-debit-notes::general.messages.marked_open'),
        ]);

        flash(trans('credit-debit-notes::general.messages.marked_open'))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function cancel(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())->findOrFail($id);

        $debitNote->update(['status' => DebitNote::STATUS_CANCELLED]);

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => DebitNote::STATUS_CANCELLED,
            'description' => trans('credit-debit-notes::general.messages.cancelled'),
        ]);

        flash(trans('credit-debit-notes::general.messages.cancelled'))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function convertToBill(int $id): Response|mixed
    {
        $debitNote = DebitNote::where('company_id', company_id())
            ->with(['items.taxes', 'totals'])
            ->findOrFail($id);

        $bill = Document::create([
            'company_id' => company_id(),
            'type' => Document::BILL_TYPE,
            'document_number' => $this->getNextBillNumber(),
            'status' => 'draft',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'amount' => $debitNote->amount,
            'currency_code' => $debitNote->currency_code,
            'currency_rate' => $debitNote->currency_rate,
            'category_id' => $debitNote->category_id,
            'contact_id' => $debitNote->contact_id,
            'contact_name' => $debitNote->contact_name,
            'contact_email' => $debitNote->contact_email,
            'contact_tax_number' => $debitNote->contact_tax_number,
            'contact_phone' => $debitNote->contact_phone,
            'contact_address' => $debitNote->contact_address,
            'contact_country' => $debitNote->contact_country,
            'contact_state' => $debitNote->contact_state,
            'contact_zip_code' => $debitNote->contact_zip_code,
            'contact_city' => $debitNote->contact_city,
            'discount_type' => $debitNote->discount_type,
            'discount_rate' => $debitNote->discount_rate,
            'notes' => $debitNote->notes,
            'footer' => $debitNote->footer,
            'parent_id' => $debitNote->id,
            'created_from' => 'credit-debit-notes::debit-notes.convert',
            'created_by' => auth()->id(),
        ]);

        foreach ($debitNote->items as $item) {
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

        foreach ($debitNote->totals as $total) {
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

        NoteHistory::create([
            'company_id' => company_id(),
            'document_id' => $debitNote->id,
            'status' => $debitNote->status,
            'description' => trans('credit-debit-notes::general.messages.converted_to_bill', [
                'bill' => $bill->document_number,
            ]),
        ]);

        flash(trans('credit-debit-notes::general.messages.converted_bill_success', [
            'bill' => $bill->document_number,
        ]))->success();

        return redirect()->route('credit-debit-notes.debit-notes.show', $debitNote->id);
    }

    public function pdf(int $id)
    {
        $debitNote = DebitNote::where('company_id', company_id())
            ->with(['contact', 'items.taxes', 'totals', 'company'])
            ->findOrFail($id);

        $html = view('credit-debit-notes::debit-notes.pdf', compact('debitNote'))->render();

        $filename = $debitNote->document_number . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($filename);
        }

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    protected function saveItems(DebitNote $debitNote, array $items): void
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
                'type' => DebitNote::NOTE_TYPE,
                'document_id' => $debitNote->id,
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
                    'type' => DebitNote::NOTE_TYPE,
                    'document_id' => $debitNote->id,
                    'document_item_id' => $documentItem->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name,
                    'amount' => $taxAmount,
                ]);
            }
        }
    }

    protected function calculateTotals(DebitNote $debitNote): void
    {
        $debitNote->totals()->delete();
        $debitNote->load('items.taxes');

        $subTotal = $debitNote->items->sum('total');
        $taxTotal = $debitNote->items->sum('tax');
        $discountTotal = 0;

        if ($debitNote->discount_rate > 0) {
            if ($debitNote->discount_type === 'percentage') {
                $discountTotal = $subTotal * ($debitNote->discount_rate / 100);
            } else {
                $discountTotal = $debitNote->discount_rate;
            }
        }

        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => DebitNote::NOTE_TYPE,
            'document_id' => $debitNote->id,
            'code' => 'sub_total',
            'name' => 'credit-debit-notes::general.sub_total',
            'amount' => $subTotal,
            'sort_order' => 1,
        ]);

        if ($discountTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => DebitNote::NOTE_TYPE,
                'document_id' => $debitNote->id,
                'code' => 'discount',
                'name' => 'credit-debit-notes::general.discount',
                'amount' => $discountTotal,
                'sort_order' => 2,
            ]);
        }

        if ($taxTotal > 0) {
            DocumentTotal::create([
                'company_id' => company_id(),
                'type' => DebitNote::NOTE_TYPE,
                'document_id' => $debitNote->id,
                'code' => 'tax',
                'name' => 'credit-debit-notes::general.tax',
                'amount' => $taxTotal,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => company_id(),
            'type' => DebitNote::NOTE_TYPE,
            'document_id' => $debitNote->id,
            'code' => 'total',
            'name' => 'credit-debit-notes::general.total',
            'amount' => $grandTotal,
            'sort_order' => 4,
        ]);

        $debitNote->update(['amount' => $grandTotal]);
    }

    protected function postDebitNoteJournal(DebitNote $debitNote): void
    {
        $companyId = $debitNote->company_id;

        $apDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'accounts_payable')->first();
        $expenseDefault = AccountDefault::where('company_id', $companyId)
            ->where('type', 'expense')->first();

        if (! $apDefault || ! $expenseDefault) {
            return;
        }

        $journal = Journal::create([
            'company_id' => $companyId,
            'date' => $debitNote->issued_at ?? $debitNote->created_at,
            'reference' => 'DN-' . $debitNote->document_number,
            'description' => 'Debit note ' . $debitNote->document_number . ' for bill ' . ($debitNote->linkedBill?->document_number ?? 'N/A'),
            'basis' => 'accrual',
            'status' => 'posted',
            'documentable_type' => get_class($debitNote),
            'documentable_id' => $debitNote->id,
            'created_by' => auth()->id(),
        ]);

        // DR Accounts Payable / CR Expense
        $journal->lines()->create([
            'account_id' => $apDefault->account_id,
            'debit' => $debitNote->amount,
            'credit' => 0,
            'description' => 'Vendor credit - ' . $debitNote->document_number,
        ]);

        $journal->lines()->create([
            'account_id' => $expenseDefault->account_id,
            'debit' => 0,
            'credit' => $debitNote->amount,
            'description' => 'Expense return - ' . $debitNote->document_number,
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
