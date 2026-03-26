<?php

namespace Modules\Inventory\Listeners;

use App\Events\Document\DocumentCreated as Event;
use Modules\Inventory\Services\InventoryService;

class DocumentCreated
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function handle(Event $event): void
    {
        $this->inventory->applyDocument($event->document, false);
    }
}
