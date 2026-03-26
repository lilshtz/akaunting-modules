<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Http\Requests\PayrollRunReviewUpdate;
use Modules\Payroll\Http\Requests\PayrollRunStore;
use Modules\Payroll\Models\PayCalendar;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Services\PayrollCalculator;
use Modules\Payroll\Services\PayrollJournalService;

class PayrollRuns extends Controller
{
    public function __construct(
        protected PayrollCalculator $calculator,
        protected PayrollJournalService $journalService
    ) {
    }

    public function index(Request $request)
    {
        $query = PayrollRun::where('company_id', company_id())
            ->with(['calendar', 'approver'])
            ->orderByDesc('period_end')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $runs = $query->paginate(25);
        $calendars = PayCalendar::where('company_id', company_id())->orderBy('name')->pluck('name', 'id');

        return view('payroll::payroll-runs.index', compact('runs', 'calendars'));
    }

    public function create()
    {
        $calendars = PayCalendar::where('company_id', company_id())
            ->withCount('employees')
            ->enabled()
            ->orderBy('name')
            ->get();
        $selectedCalendarId = request('pay_calendar_id');

        return view('payroll::payroll-runs.create', compact('calendars', 'selectedCalendarId'));
    }

    public function store(PayrollRunStore $request)
    {
        $calendar = PayCalendar::where('company_id', company_id())
            ->with('employees')
            ->findOrFail($request->integer('pay_calendar_id'));

        abort_if($calendar->employees->isEmpty(), 422, trans('payroll::general.no_calendar_employees'));

        $periodEnd = Carbon::parse($calendar->next_run_date);
        $periodStart = $this->determinePeriodStart($calendar, $periodEnd);
        $payload = $this->calculator->buildRunLines($calendar, $periodStart, $periodEnd);

        DB::transaction(function () use ($calendar, $periodStart, $periodEnd, $payload, &$run) {
            $run = PayrollRun::create([
                'company_id' => company_id(),
                'pay_calendar_id' => $calendar->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'review',
                'total_gross' => $payload['totals']['gross'],
                'total_deductions' => $payload['totals']['deductions'],
                'total_net' => $payload['totals']['net'],
            ]);

            foreach ($payload['lines'] as $line) {
                $run->employees()->create($line);
            }
        });

        flash(trans('messages.success.added', ['type' => trans('payroll::general.payroll_run')]))->success();

        return redirect()->route('payroll.payroll-runs.show', $run->id);
    }

    public function show(int $id)
    {
        $run = $this->loadRun($id);

        return view('payroll::payroll-runs.show', compact('run'));
    }

    public function update(int $id, PayrollRunReviewUpdate $request)
    {
        $run = $this->loadRun($id);

        if (! in_array($run->status, ['draft', 'review'], true)) {
            flash(trans('payroll::general.run_locked'))->warning();

            return redirect()->route('payroll.payroll-runs.show', $run->id);
        }

        DB::transaction(function () use ($run, $request) {
            foreach ($run->employees as $line) {
                $input = $request->get('lines')[$line->id] ?? null;

                if (! $input) {
                    continue;
                }

                $gross = round((float) ($input['gross_amount'] ?? 0), 4);
                $benefits = round((float) ($input['benefit_amount'] ?? 0), 4);
                $deductions = round((float) ($input['deduction_amount'] ?? 0), 4);

                $line->update([
                    'gross_amount' => $gross,
                    'benefit_amount' => $benefits,
                    'deduction_amount' => $deductions,
                    'net_amount' => max(0, round($gross + $benefits - $deductions, 4)),
                    'notes' => $input['notes'] ?? null,
                ]);
            }

            $this->refreshTotals($run);
            $run->update(['status' => 'review']);
        });

        flash(trans('messages.success.updated', ['type' => trans('payroll::general.payroll_run')]))->success();

        return redirect()->route('payroll.payroll-runs.show', $run->id);
    }

    public function approve(int $id)
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'review') {
            flash(trans('payroll::general.approval_requires_review'))->warning();

            return redirect()->route('payroll.payroll-runs.show', $run->id);
        }

        $run->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        flash(trans('payroll::general.run_approved'))->success();

        return redirect()->route('payroll.payroll-runs.show', $run->id);
    }

    public function process(int $id)
    {
        $run = $this->loadRun($id);

        if ($run->status !== 'approved') {
            flash(trans('payroll::general.process_requires_approval'))->warning();

            return redirect()->route('payroll.payroll-runs.show', $run->id);
        }

        DB::transaction(function () use ($run) {
            $this->journalService->post($run);

            $run->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            $run->calendar->update([
                'next_run_date' => $this->nextRunDate($run->calendar, Carbon::parse($run->period_end))->toDateString(),
            ]);
        });

        flash(trans('payroll::general.run_processed'))->success();

        return redirect()->route('payroll.payroll-runs.show', $run->id);
    }

    protected function loadRun(int $id): PayrollRun
    {
        return PayrollRun::where('company_id', company_id())
            ->with(['calendar', 'approver', 'employees.employee.contact'])
            ->findOrFail($id);
    }

    protected function refreshTotals(PayrollRun $run): void
    {
        $run->refresh()->load('employees');

        $run->update([
            'total_gross' => round($run->employees->sum(fn ($line) => $line->gross_amount + $line->benefit_amount), 4),
            'total_deductions' => round($run->employees->sum('deduction_amount'), 4),
            'total_net' => round($run->employees->sum('net_amount'), 4),
        ]);
    }

    protected function determinePeriodStart(PayCalendar $calendar, Carbon $periodEnd): Carbon
    {
        $lastRun = $calendar->runs()->latest('period_end')->first();

        if ($lastRun) {
            return Carbon::parse($lastRun->period_end)->addDay();
        }

        return match ($calendar->frequency) {
            'weekly' => (clone $periodEnd)->subDays(6),
            'biweekly' => (clone $periodEnd)->subDays(13),
            'monthly' => (clone $periodEnd)->startOfMonth(),
            'custom' => Carbon::parse($calendar->start_date),
            default => Carbon::parse($calendar->start_date),
        };
    }

    protected function nextRunDate(PayCalendar $calendar, Carbon $current): Carbon
    {
        return match ($calendar->frequency) {
            'weekly' => $current->copy()->addWeek(),
            'biweekly' => $current->copy()->addWeeks(2),
            'monthly' => $current->copy()->addMonth(),
            'custom' => $current->copy()->addDays(max(1, Carbon::parse($calendar->start_date)->diffInDays($calendar->next_run_date))),
            default => $current->copy()->addMonth(),
        };
    }
}
