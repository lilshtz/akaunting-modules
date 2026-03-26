<?php

namespace Modules\DoubleEntry\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Journal;

class ProcessRecurringJournals extends Command
{
    protected $signature = 'double-entry:process-recurring';

    protected $description = 'Create new journal entries from recurring templates that are due';

    public function handle(): int
    {
        $today = now()->toDateString();

        $recurringJournals = Journal::whereNotNull('recurring_frequency')
            ->whereNotNull('next_recurring_date')
            ->where('next_recurring_date', '<=', $today)
            ->where('status', 'posted')
            ->with('lines')
            ->get();

        $count = 0;

        foreach ($recurringJournals as $template) {
            DB::transaction(function () use ($template, $today) {
                // Create new journal from template
                $journal = Journal::create([
                    'company_id' => $template->company_id,
                    'date' => $today,
                    'reference' => $template->reference,
                    'description' => $template->description,
                    'basis' => $template->basis,
                    'status' => 'posted',
                    'created_by' => $template->created_by,
                ]);

                foreach ($template->lines as $line) {
                    $journal->lines()->create([
                        'account_id' => $line->account_id,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'description' => $line->description,
                    ]);
                }

                // Advance the next recurring date
                $baseDate = $template->next_recurring_date->copy();
                $nextDate = match ($template->recurring_frequency) {
                    'weekly' => $baseDate->addWeek(),
                    'monthly' => $baseDate->addMonth(),
                    'quarterly' => $baseDate->addMonths(3),
                    'yearly' => $baseDate->addYear(),
                    default => null,
                };

                $template->update([
                    'next_recurring_date' => $nextDate?->toDateString(),
                ]);
            });

            $count++;
        }

        $this->info("Processed {$count} recurring journal entries.");

        return self::SUCCESS;
    }
}
