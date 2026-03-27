<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Projects\Http\Requests\TimesheetStore;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectActivity;
use Modules\Projects\Models\ProjectTask;
use Modules\Projects\Models\ProjectTimesheet;

class Timesheets extends Controller
{
    public function store(TimesheetStore $request, int $projectId): Response
    {
        $project = $this->findProject($projectId);
        $task = $project->tasks()->findOrFail($request->integer('task_id'));

        $startedAt = Carbon::parse($request->get('work_date'))->startOfDay();
        $hours = round((float) $request->get('hours'), 2);
        $endedAt = $startedAt->copy()->addSeconds((int) round($hours * 3600));

        $timesheet = ProjectTimesheet::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'hours' => $hours,
            'billable' => $request->boolean('billable', true),
            'description' => $request->get('description'),
        ]);

        ProjectActivity::log($project, 'timesheet_logged', 'timesheet', $timesheet->id, trans('projects::general.activity.timesheet_logged', [
            'task' => $task->name,
            'hours' => number_format($timesheet->tracked_hours, 2),
        ]));

        flash(trans('messages.success.added', ['type' => trans('projects::general.timesheet')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'timesheets']);
    }

    public function start(Request $request, int $projectId, int $taskId): Response
    {
        $project = $this->findProject($projectId);
        $task = $project->tasks()->findOrFail($taskId);
        $activeTimer = $this->findActiveTimer((int) auth()->id());

        if ($activeTimer && (int) $activeTimer->task_id === $task->id) {
            flash(trans('projects::general.timer_already_running'))->warning();

            return redirect()->route('projects.tasks.edit', $task->id);
        }

        if ($activeTimer) {
            $this->stopActiveTimer($activeTimer);
        }

        $timesheet = ProjectTimesheet::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'started_at' => now(),
            'billable' => true,
        ]);

        ProjectActivity::log($project, 'timer_started', 'timesheet', $timesheet->id, trans('projects::general.activity.timer_started', [
            'task' => $task->name,
        ]));

        flash(trans('projects::general.timer_started_message'))->success();

        return redirect()->back();
    }

    public function stop(Request $request, int $projectId, int $taskId): Response
    {
        $project = $this->findProject($projectId);
        $task = $project->tasks()->findOrFail($taskId);

        $timesheet = ProjectTimesheet::where('task_id', $task->id)
            ->where('user_id', auth()->id())
            ->running()
            ->latest('started_at')
            ->firstOrFail();

        $this->finishTimer($timesheet);

        ProjectActivity::log($project, 'timer_stopped', 'timesheet', $timesheet->id, trans('projects::general.activity.timer_stopped', [
            'task' => $task->name,
            'hours' => number_format($timesheet->tracked_hours, 2),
        ]));

        flash(trans('projects::general.timer_stopped_message'))->success();

        return redirect()->back();
    }

    protected function findProject(int $id): Project
    {
        return Project::where('company_id', company_id())->findOrFail($id);
    }

    protected function findActiveTimer(int $userId): ?ProjectTimesheet
    {
        return ProjectTimesheet::with('task.project')
            ->where('user_id', $userId)
            ->running()
            ->whereHas('task.project', fn ($query) => $query->where('company_id', company_id()))
            ->latest('started_at')
            ->first();
    }

    protected function stopActiveTimer(ProjectTimesheet $timesheet): void
    {
        $this->finishTimer($timesheet);

        if ($timesheet->relationLoaded('task') && $timesheet->task?->project) {
            ProjectActivity::log($timesheet->task->project, 'timer_stopped', 'timesheet', $timesheet->id, trans('projects::general.activity.timer_stopped', [
                'task' => $timesheet->task->name,
                'hours' => number_format($timesheet->tracked_hours, 2),
            ]));
        }
    }

    protected function finishTimer(ProjectTimesheet $timesheet): void
    {
        $endedAt = now();
        $hours = max(0.01, round($timesheet->started_at->diffInSeconds($endedAt) / 3600, 2));

        $timesheet->update([
            'ended_at' => $endedAt,
            'hours' => $hours,
        ]);
    }
}
