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

        Journal::where('company_id', $document->company_id)
            ->where('reference', $reference)
            ->where('status', 'posted')
            ->update(['status' => 'voided']);

        (new DocumentCreated())->createFromDocument($document);
    }
}
