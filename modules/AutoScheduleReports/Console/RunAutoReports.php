<?php

namespace Modules\AutoScheduleReports\Console;

use Illuminate\Console\Command;
use Modules\AutoScheduleReports\Models\ReportSchedule;
use Modules\AutoScheduleReports\Services\ScheduleRunner;

class RunAutoReports extends Command
{
    protected $signature = 'auto-reports:run';

    protected $description = 'Generate and deliver due scheduled reports';

    public function __construct(protected ScheduleRunner $runner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $schedules = ReportSchedule::where('enabled', true)
            ->whereNotNull('next_run')
            ->where('next_run', '<=', now())
            ->orderBy('next_run')
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            $run = $this->runner->run($schedule, true);
            $this->line('Schedule #' . $schedule->id . ': ' . $run->status);
            $count++;
        }

        $this->info('Processed ' . $count . ' scheduled reports.');

        return self::SUCCESS;
    }
}
