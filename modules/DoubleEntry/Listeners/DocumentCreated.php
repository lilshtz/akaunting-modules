<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentCreated as Event;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class DocumentCreated
{
    /**
     * Handle the event.
     *
     * Auto-post journal entries when invoices/bills are created.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $this->createFromDocument($event->document);
    }

    public function createFromDocument($document)
    {
        if (in_array($document->status, ['draft', 'cancelled'], true)) {
            return;
        }

        $companyId = $document->company_id;
        $isInvoice = str_contains($document->type, 'invoice');
        $reference = $document->type . ':' . $document->id;

        if ($isInvoice) {
            $receivableDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'accounts_receivable')->first();
            $incomeDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'sales_income')->first();

            if (!$receivableDefault || !$incomeDefault) {
                return;
            }

            $debitAccountId = $receivableDefault->account_id;
            $creditAccountId = $incomeDefault->account_id;
        } else {
            $payableDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'accounts_payable')->first();
            $expenseDefault = AccountDefault::where('company_id', $companyId)
                ->where('type', 'cost_of_sales')->first();

            if (!$payableDefault || !$expenseDefault) {
                return;
            }

            $debitAccountId = $expenseDefault->account_id;
            $creditAccountId = $payableDefault->account_id;
        }

        $journal = Journal::firstOrCreate([
            'company_id' => $companyId,
            'reference' => $reference,
        ], [
            'number' => 'JE-AUTO-' . strtoupper(substr(md5($document->id . $document->type), 0, 8)),
            'date' => $document->issued_at ?? $document->created_at,
            'description' => ($isInvoice ? 'Invoice' : 'Bill') . ' #' . $document->document_number,
            'status' => 'posted',
        ]);

        if ($journal->lines()->exists()) {
            return;
        }

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $debitAccountId,
            'debit' => $document->amount,
            'credit' => 0,
            'description' => $journal->description,
        ]);

        JournalLine::create([
            'company_id' => $companyId,
            'journal_id' => $journal->id,
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $document->amount,
            'description' => $journal->description,
        ]);
    }
}
