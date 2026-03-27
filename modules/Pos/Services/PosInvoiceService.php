<?php

namespace Modules\Pos\Services;

use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentTotal;
use Modules\Pos\Models\PosOrder;
use Modules\Pos\Models\PosSetting;

class PosInvoiceService
{
    public function createFromOrder(PosOrder $order, PosSetting $setting): ?Document
    {
        if (! $setting->auto_create_invoice) {
            return null;
        }

        $contact = $order->contact;
        $currencyCode = setting('default.currency', 'USD');
        $invoice = Document::create([
            'company_id' => $order->company_id,
            'type' => Document::INVOICE_TYPE,
            'document_number' => $this->nextInvoiceNumber(),
            'status' => 'paid',
            'issued_at' => $order->created_at ?? now(),
            'due_at' => $order->created_at ?? now(),
            'amount' => $order->total,
            'currency_code' => $currencyCode,
            'currency_rate' => 1,
            'contact_id' => $order->contact_id,
            'contact_name' => $order->contact_id ? $contact->name : trans('pos::general.walk_in_customer'),
            'contact_email' => $contact->email,
            'contact_tax_number' => $contact->tax_number,
            'contact_phone' => $contact->phone,
            'contact_address' => $contact->address,
            'contact_country' => $contact->country,
            'contact_state' => $contact->state,
            'contact_zip_code' => $contact->zip_code,
            'contact_city' => $contact->city,
            'notes' => trans('pos::general.messages.invoice_from_order', ['order' => $order->order_number]),
            'created_from' => 'pos::orders.store',
            'created_by' => auth()->id(),
        ]);

        foreach ($order->items as $item) {
            DocumentItem::create([
                'company_id' => $order->company_id,
                'type' => Document::INVOICE_TYPE,
                'document_id' => $invoice->id,
                'item_id' => $item->item_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'tax' => $item->tax,
                'discount_rate' => $item->discount,
                'discount_type' => 'fixed',
                'total' => $item->total,
            ]);
        }

        DocumentTotal::create([
            'company_id' => $order->company_id,
            'type' => Document::INVOICE_TYPE,
            'document_id' => $invoice->id,
            'code' => 'sub_total',
            'name' => 'general.sub_total',
            'amount' => $order->subtotal,
            'sort_order' => 1,
        ]);

        if ((float) $order->discount > 0) {
            DocumentTotal::create([
                'company_id' => $order->company_id,
                'type' => Document::INVOICE_TYPE,
                'document_id' => $invoice->id,
                'code' => 'discount',
                'name' => 'general.discount',
                'amount' => -1 * (float) $order->discount,
                'sort_order' => 2,
            ]);
        }

        if ((float) $order->tax > 0) {
            DocumentTotal::create([
                'company_id' => $order->company_id,
                'type' => Document::INVOICE_TYPE,
                'document_id' => $invoice->id,
                'code' => 'tax',
                'name' => 'general.tax',
                'amount' => $order->tax,
                'sort_order' => 3,
            ]);
        }

        DocumentTotal::create([
            'company_id' => $order->company_id,
            'type' => Document::INVOICE_TYPE,
            'document_id' => $invoice->id,
            'code' => 'total',
            'name' => 'general.total',
            'amount' => $order->total,
            'sort_order' => 4,
        ]);

        return $invoice;
    }

    protected function nextInvoiceNumber(): string
    {
        $prefix = (string) setting('invoice.number_prefix', 'INV-');
        $next = (int) setting('invoice.number_next', 1);
        $digits = (int) setting('invoice.number_digit', 4);

        setting(['invoice.number_next' => $next + 1]);
        setting()->save();

        return $prefix . str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
    }
}
