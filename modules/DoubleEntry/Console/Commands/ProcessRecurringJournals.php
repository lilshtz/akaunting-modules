<?php

namespace Modules\DoubleEntry\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\DoubleEntry\Models\Journal;

class ProcessRecurringJournals extends Command
{
    protected $signature = 'double-entry:process-recurring';

    protected $description = 'Create new journal entries from recurring templates';

    public function handle(): int
    {
        $today = Carbon::today();

        $recurring = Journal::recurring()
            ->where('next_recurring_date', '<=', $today)
            ->where('status', 'posted')
            ->with('lines')
            ->get();

        $count = 0;

        foreach ($recurring as $template) {
            $newJournal = Journal::create([
                'company_id' => $template->company_id,
                'date' => $template->next_recurring_date,
                'reference' => $template->reference,
                'description' => $template->description,
                'basis' => $template->basis,
                'status' => 'posted',
                'created_by' => $template->created_by,
            ]);

            foreach ($template->lines as $line) {
                $newJournal->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'description' => $line->description,
                ]);
            }

            // Advance the next recurring date
            $nextDate = match ($template->recurring_frequency) {
                'weekly' => Carbon::parse($template->next_recurring_date)->addWeek(),
                'monthly' => Carbon::parse($template->next_recurring_date)->addMonth(),
                'quarterly' => Carbon::parse($template->next_recurring_date)->addMonths(3),
                'yearly' => Carbon::parse($template->next_recurring_date)->addYear(),
                default => null,
            };

            $template->update(['next_recurring_date' => $nextDate]);

            $count++;
        }

        $this->info("Processed {$count} recurring journal entries.");

        return self::SUCCESS;
    }
}
