<?php

namespace Modules\AutoScheduleReports\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\AutoScheduleReports\Models\ReportSchedule;
use Modules\AutoScheduleReports\Models\ReportScheduleRun;

class ScheduleRunner
{
    public function __construct(
        protected ReportBuilder $reportBuilder,
        protected NextRunCalculator $nextRunCalculator
    ) {
    }

    public function run(ReportSchedule $schedule, bool $advanceSchedule = true): ReportScheduleRun
    {
        $run = $schedule->runs()->create([
            'ran_at' => now(),
            'status' => 'failed',
        ]);

        try {
            $generated = $this->reportBuilder->generate($schedule);
            $delivered = $this->deliver($schedule, $generated);

            $run->update([
                'file_path' => $generated['path'],
                'status' => 'success',
                'error_message' => null,
                'emailed_at' => $delivered ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Scheduled report run failed', [
                'schedule_id' => $schedule->id,
                'company_id' => $schedule->company_id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        if ($advanceSchedule && $schedule->enabled) {
            $base = $schedule->next_run?->copy() ?? now();

            $schedule->update([
                'next_run' => $this->nextRunCalculator->calculate($schedule->frequency, $base, true),
            ]);
        }

        return $run->fresh();
    }

    protected function deliver(ReportSchedule $schedule, array $generated): bool
    {
        $delivered = false;
        $recipients = array_filter((array) $schedule->recipients_json);
        $fileContents = Storage::disk('local')->get($generated['path']);

        if (! empty($recipients)) {
            Mail::send('auto-schedule-reports::reports.email', [
                'schedule' => $schedule,
                'report' => $generated['report'],
            ], function ($message) use ($recipients, $generated, $fileContents, $schedule) {
                $message->to($recipients)
                    ->subject(trans('auto-schedule-reports::general.email_subject', [
                        'report' => $schedule->report_type_label,
                    ]))
                    ->attachData($fileContents, $generated['name'], ['mime' => $generated['mime_type']]);
            });

            $delivered = true;
        }

        if (! empty($schedule->webhook_url)) {
            Http::attach('file', $fileContents, $generated['name'])
                ->post($schedule->webhook_url, [
                    'schedule_id' => $schedule->id,
                    'report_type' => $schedule->report_type,
                    'report_name' => $schedule->report_type_label,
                    'period' => $generated['report']['date_label'],
                    'format' => $schedule->format,
                ])->throw();

            $delivered = true;
        }

        return $delivered;
    }
}
