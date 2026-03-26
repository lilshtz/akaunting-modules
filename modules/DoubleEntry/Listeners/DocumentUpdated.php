<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Document\DocumentUpdated as Event;
use Modules\DoubleEntry\Models\Journal;

class DocumentUpdated
{
    public function handle(Event $event): void
    {
        $document = $event->document;

        // Remove previous auto-posted journal for this document, then re-post
        Journal::where('company_id', $document->company_id)
            ->where('documentable_type', get_class($document))
            ->where('documentable_id', $document->id)
            ->each(function ($journal) {
                $journal->lines()->delete();
                $journal->forceDelete();
            });

        // Re-trigger creation logic
        $createdEvent = new \App\Events\Document\DocumentCreated($document);
        (new DocumentCreated())->handle($createdEvent);
    }
}
