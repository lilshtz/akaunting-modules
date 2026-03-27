<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentUpdated as Event;
use Modules\DoubleEntry\Services\AccountBalanceService;

class DocumentUpdated
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function handle(Event $event): void
    {
        $document = $event->document;
        $defaults = $this->service->defaultMappings();
        $amount = round((float) ($document->amount ?? 0), 4);

        if ($amount <= 0) {
            return;
        }

        if ($document->type === 'invoice' && isset($defaults['accounts_receivable'], $defaults['sales'])) {
            $this->service->upsertAutoJournal('document', (int) $document->id, [
                'date' => optional($document->issued_at)->format('Y-m-d') ?: now()->toDateString(),
                'reference' => $document->document_number ?? $document->number ?? null,
                'description' => 'Auto-posted invoice journal',
            ], [
                ['account_id' => $defaults['accounts_receivable']->account_id, 'entry_type' => 'debit', 'amount' => $amount, 'description' => $document->notes],
                ['account_id' => $defaults['sales']->account_id, 'entry_type' => 'credit', 'amount' => $amount, 'description' => $document->notes],
            ]);
        }

        if ($document->type === 'bill' && isset($defaults['purchases'], $defaults['accounts_payable'])) {
            $this->service->upsertAutoJournal('document', (int) $document->id, [
                'date' => optional($document->issued_at)->format('Y-m-d') ?: now()->toDateString(),
                'reference' => $document->document_number ?? $document->number ?? null,
                'description' => 'Auto-posted bill journal',
            ], [
                ['account_id' => $defaults['purchases']->account_id, 'entry_type' => 'debit', 'amount' => $amount, 'description' => $document->notes],
                ['account_id' => $defaults['accounts_payable']->account_id, 'entry_type' => 'credit', 'amount' => $amount, 'description' => $document->notes],
            ]);
        }
    }
}
