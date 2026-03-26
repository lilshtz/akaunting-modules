<?php

namespace Modules\ExpenseClaims\Services;

use App\Models\Banking\Transaction;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentTotal;
use App\Models\Common\Contact;
use Illuminate\Support\Str;
use Modules\ExpenseClaims\Models\ExpenseClaim;

class ReimbursementService
{
    public function createBill(ExpenseClaim $claim): ?Document
    {
        if ($claim->reimbursement_document_id || $claim->reimbursable_total <= 0) {
            return $claim->reimbursementDocument;
        }

        $employee = $claim->employee()->with('contact')->first();
        $contact = $this->resolveEmployeeContact($claim);

        $bill = Document::create([
            'company_id' => $claim->company_id,
            'type' => Document::BILL_TYPE,
            'document_number' => $this->generateBillNumber(),
            'status' => 'received',
            'issued_at' => $claim->approved_at ?? now(),
            'due_at' => $claim->due_date ?? now()->addDays(30),
            'amount' => $claim->reimbursable_total,
            'currency_code' => setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'category_id' => $claim->items()->value('category_id'),
            'contact_id' => $contact?->id,
            'contact_name' => $employee?->name ?? trans('general.na'),
            'contact_email' => $contact?->email,
            'notes' => trans('expense-claims::general.messages.created_bill', ['claim' => $claim->claim_number ?: '#' . $claim->id]),
            'created_from' => 'expense-claims::claims',
            'created_by' => auth()->id(),
        ]);

        DocumentItem::create([
            'company_id' => $claim->company_id,
            'type' => Document::BILL_TYPE,
            'document_id' => $bill->id,
            'name' => trans('expense-claims::general.reimbursement_for', ['claim' => $claim->claim_number ?: '#' . $claim->id]),
            'quantity' => 1,
            'price' => $claim->reimbursable_total,
            'tax' => 0,
            'total' => $claim->reimbursable_total,
        ]);

        DocumentTotal::create([
            'company_id' => $claim->company_id,
            'type' => Document::BILL_TYPE,
            'document_id' => $bill->id,
            'code' => 'sub_total',
            'name' => 'general.sub_total',
            'amount' => $claim->reimbursable_total,
            'sort_order' => 1,
        ]);

        DocumentTotal::create([
            'company_id' => $claim->company_id,
            'type' => Document::BILL_TYPE,
            'document_id' => $bill->id,
            'code' => 'total',
            'name' => 'general.total',
            'amount' => $claim->reimbursable_total,
            'sort_order' => 2,
        ]);

        $claim->update(['reimbursement_document_id' => $bill->id]);

        return $bill;
    }

    public function createPayment(ExpenseClaim $claim): ?Transaction
    {
        if ($claim->reimbursable_total <= 0) {
            return null;
        }

        if ($claim->reimbursement_transaction_id) {
            return $claim->reimbursementTransaction;
        }

        $contact = $this->resolveEmployeeContact($claim);

        $payment = Transaction::create([
            'company_id' => $claim->company_id,
            'type' => Transaction::EXPENSE_TYPE,
            'account_id' => setting('default.account'),
            'paid_at' => now(),
            'amount' => $claim->reimbursable_total,
            'currency_code' => setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'document_id' => $claim->reimbursement_document_id,
            'contact_id' => $contact?->id,
            'category_id' => $claim->items()->value('category_id'),
            'description' => trans('expense-claims::general.reimbursement_for', ['claim' => $claim->claim_number ?: '#' . $claim->id]),
            'payment_method' => 'offline-payments.cash.1',
            'reference' => Str::limit((string) $claim->description, 100),
            'created_from' => 'expense-claims::claims',
            'created_by' => auth()->id(),
        ]);

        $claim->update(['reimbursement_transaction_id' => $payment->id]);

        return $payment;
    }

    protected function resolveEmployeeContact(ExpenseClaim $claim): ?Contact
    {
        $employee = $claim->employee()->with('contact')->first();

        if (! $employee) {
            return null;
        }

        return Contact::firstOrCreate(
            [
                'company_id' => $claim->company_id,
                'type' => 'vendor',
                'name' => $employee->name,
            ],
            [
                'email' => $employee->contact?->email ?? $employee->email,
                'enabled' => true,
                'currency_code' => setting('default.currency', 'USD'),
                'phone' => $employee->contact?->phone,
                'address' => $employee->contact?->address,
            ]
        );
    }

    protected function generateBillNumber(): string
    {
        $prefix = setting('bill.number_prefix', 'BILL-');
        $next = (int) setting('bill.number_next', 1);
        $number = $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        setting(['bill.number_next' => $next + 1]);
        setting()->save();

        return $number;
    }
}
