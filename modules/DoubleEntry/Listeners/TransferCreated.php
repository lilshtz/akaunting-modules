<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Banking\TransferCreated as Event;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TransferCreated
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function handle(Event $event): void
    {
        $transfer = $event->transfer;
        $defaults = $this->service->defaultMappings();
        $amount = round((float) ($transfer->amount ?? 0), 4);

        if ($amount <= 0 || !isset($defaults['cash'], $defaults['bank'])) {
            return;
        }

        $this->service->upsertAutoJournal('transfer', (int) $transfer->id, [
            'date' => optional($transfer->created_at)->format('Y-m-d') ?: now()->toDateString(),
            'reference' => (string) $transfer->id,
            'description' => 'Auto-posted transfer journal',
        ], [
            ['account_id' => $defaults['bank']->account_id, 'entry_type' => 'debit', 'amount' => $amount, 'description' => 'Transfer in'],
            ['account_id' => $defaults['cash']->account_id, 'entry_type' => 'credit', 'amount' => $amount, 'description' => 'Transfer out'],
        ]);
    }
}
