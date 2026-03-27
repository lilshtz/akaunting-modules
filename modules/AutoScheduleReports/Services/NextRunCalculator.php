<?php

namespace Modules\AutoScheduleReports\Services;

use Illuminate\Support\Carbon;

class NextRunCalculator
{
    public function calculate(string $frequency, ?Carbon $from = null, bool $ensureFuture = false): Carbon
    {
        $nextRun = $from ? $from->copy() : now();
        $nextRun = $this->addInterval($nextRun, $frequency);

        if ($ensureFuture) {
            while ($nextRun->lte(now())) {
                $nextRun = $this->addInterval($nextRun, $frequency);
            }
        }

        return $nextRun;
    }

    protected function addInterval(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addQuarter(),
            default => $date->addYear(),
        };
    }
}
