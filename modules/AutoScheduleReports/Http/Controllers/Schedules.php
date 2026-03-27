<?php

namespace Modules\AutoScheduleReports\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\AutoScheduleReports\Http\Requests\ScheduleStore;
use Modules\AutoScheduleReports\Http\Requests\ScheduleUpdate;
use Modules\AutoScheduleReports\Models\ReportSchedule;
use Modules\AutoScheduleReports\Models\ReportScheduleRun;
use Modules\AutoScheduleReports\Services\NextRunCalculator;
use Modules\AutoScheduleReports\Services\ScheduleRunner;

class Schedules extends Controller
{
    public function __construct(
        protected ScheduleRunner $runner,
        protected NextRunCalculator $nextRunCalculator
    ) {
    }

    public function index(Request $request): Response
    {
        $query = ReportSchedule::forCompany(company_id())
            ->withCount('runs')
            ->with(['runs' => fn ($builder) => $builder->latest('ran_at')])
            ->orderByDesc('next_run')
            ->orderByDesc('id');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->get('report_type'));
        }

        if ($request->filled('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $schedules = $query->paginate(20);

        return view('auto-schedule-reports::schedules.index', array_merge($this->formData(), compact('schedules')));
    }

    public function create(): Response
    {
        $schedule = new ReportSchedule([
            'report_type' => 'pnl',
            'frequency' => 'monthly',
            'format' => 'pdf',
            'date_range_type' => 'previous_month',
            'next_run' => $this->nextRunCalculator->calculate('monthly'),
            'enabled' => true,
        ]);

        return view('auto-schedule-reports::schedules.create', array_merge($this->formData(), compact('schedule')));
    }

    public function store(ScheduleStore $request): Response
    {
        ReportSchedule::create([
            'company_id' => company_id(),
            'report_type' => $request->get('report_type'),
            'frequency' => $request->get('frequency'),
            'next_run' => $request->get('next_run'),
            'recipients_json' => $request->get('recipients_json', []),
            'format' => $request->get('format'),
            'date_range_type' => $request->get('date_range_type'),
            'custom_date_from' => $request->get('custom_date_from'),
            'custom_date_to' => $request->get('custom_date_to'),
            'webhook_url' => $request->get('webhook_url'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        flash(trans('messages.success.added', ['type' => trans('auto-schedule-reports::general.schedule')]))->success();

        return redirect()->route('auto-schedule-reports.schedules.index');
    }

    public function show(int $id): Response
    {
        $schedule = $this->findSchedule($id);
        $runs = $schedule->runs()->latest('ran_at')->paginate(15);

        return view('auto-schedule-reports::schedules.show', compact('schedule', 'runs'));
    }

    public function edit(int $id): Response
    {
        $schedule = $this->findSchedule($id);

        return view('auto-schedule-reports::schedules.edit', array_merge($this->formData(), compact('schedule')));
    }

    public function update(int $id, ScheduleUpdate $request): Response
    {
        $schedule = $this->findSchedule($id);

        $schedule->update([
            'report_type' => $request->get('report_type'),
            'frequency' => $request->get('frequency'),
            'next_run' => $request->get('next_run'),
            'recipients_json' => $request->get('recipients_json', []),
            'format' => $request->get('format'),
            'date_range_type' => $request->get('date_range_type'),
            'custom_date_from' => $request->get('custom_date_from'),
            'custom_date_to' => $request->get('custom_date_to'),
            'webhook_url' => $request->get('webhook_url'),
            'enabled' => $request->boolean('enabled'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('auto-schedule-reports::general.schedule')]))->success();

        return redirect()->route('auto-schedule-reports.schedules.show', $schedule->id);
    }

    public function destroy(int $id): Response
    {
        $schedule = $this->findSchedule($id);
        $schedule->delete();

        flash(trans('messages.success.deleted', ['type' => trans('auto-schedule-reports::general.schedule')]))->success();

        return redirect()->route('auto-schedule-reports.schedules.index');
    }

    public function toggle(int $id): Response
    {
        $schedule = $this->findSchedule($id);
        $schedule->update(['enabled' => ! $schedule->enabled]);

        flash(trans('auto-schedule-reports::general.messages.toggled'))->success();

        return redirect()->route('auto-schedule-reports.schedules.index');
    }

    public function runNow(int $id): Response
    {
        $schedule = $this->findSchedule($id);
        $run = $this->runner->run($schedule, false);

        if ($run->status === 'success') {
            flash(trans('auto-schedule-reports::general.messages.run_success'))->success();
        } else {
            flash(trans('auto-schedule-reports::general.messages.run_failed'))->error();
        }

        return redirect()->route('auto-schedule-reports.schedules.show', $schedule->id);
    }

    public function downloadRun(int $id): \Illuminate\Http\Response
    {
        $run = ReportScheduleRun::whereHas('schedule', fn ($query) => $query->where('company_id', company_id()))
            ->findOrFail($id);

        abort_unless($run->file_path && Storage::disk('local')->exists($run->file_path), 404);

        return Storage::disk('local')->download($run->file_path, basename($run->file_path));
    }

    protected function formData(): array
    {
        return [
            'reportTypes' => collect(ReportSchedule::REPORT_TYPES)
                ->mapWithKeys(fn ($type) => [$type => trans('auto-schedule-reports::general.report_types.' . $type)]),
            'frequencies' => collect(ReportSchedule::FREQUENCIES)
                ->mapWithKeys(fn ($frequency) => [$frequency => trans('auto-schedule-reports::general.frequencies.' . $frequency)]),
            'formats' => collect(ReportSchedule::FORMATS)
                ->mapWithKeys(fn ($format) => [$format => trans('auto-schedule-reports::general.formats.' . $format)]),
            'dateRanges' => collect(ReportSchedule::DATE_RANGE_TYPES)
                ->mapWithKeys(fn ($type) => [$type => trans('auto-schedule-reports::general.date_ranges.' . $type)]),
        ];
    }

    protected function findSchedule(int $id): ReportSchedule
    {
        return ReportSchedule::forCompany(company_id())
            ->withCount('runs')
            ->with(['runs' => fn ($query) => $query->latest('ran_at')])
            ->findOrFail($id);
    }
}
