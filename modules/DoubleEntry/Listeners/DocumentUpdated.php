<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentUpdated as Event;
use Modules\DoubleEntry\Models\Journal;

class DocumentUpdated
{
    /**
     * Handle the event.
     *
     * When a document is updated, void old journal and create new one if still posted.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $document = $event->document;
        $reference = $document->type . ':' . $document->id;

        // Void existing journals for this document
        Journal::where('company_id', $document->company_id)
            ->where('reference', $reference)
            ->where('status', 'posted')
            ->update(['status' => 'voided']);

        // Re-create if not cancelled/draft
        if (!in_array($document->status, ['draft', 'cancelled'])) {
            $listener = new DocumentCreated();
            $listener->handle(new \App\Events\Document\DocumentCreated($document));
        }
    }
}
