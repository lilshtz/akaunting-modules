<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Employees\Models\Employee;
use Modules\Projects\Http\Requests\TaskStore;
use Modules\Projects\Http\Requests\TaskUpdate;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectActivity;
use Modules\Projects\Models\ProjectTask;
use Modules\Projects\Models\ProjectTimesheet;

class Tasks extends Controller
{
    public function store(TaskStore $request, int $projectId): Response|mixed
    {
        $project = $this->findProject($projectId);

        $task = $project->tasks()->create([
            'milestone_id' => $request->get('milestone_id') ?: null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'assignee_id' => $request->get('assignee_id') ?: null,
            'priority' => $request->get('priority'),
            'status' => $request->get('status'),
            'estimated_hours' => $request->get('estimated_hours'),
            'position' => $request->get('position', (int) $project->tasks()->max('position') + 1),
        ]);

        ProjectActivity::log($project, 'task_created', 'task', $task->id, trans('projects::general.activity.task_created', [
            'name' => $task->name,
        ]));

        if ($task->status === ProjectTask::STATUS_DONE) {
            ProjectActivity::log($project, 'task_status_changed', 'task', $task->id, trans('projects::general.activity.task_status_changed', [
                'name' => $task->name,
                'status' => $task->status_label,
            ]));
        }

        flash(trans('messages.success.added', ['type' => $task->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']);
    }

    public function edit(int $id): Response|mixed
    {
        $task = ProjectTask::with(['project', 'milestone', 'assignee'])->findOrFail($id);
        $project = $this->findProject($task->project_id);
        $currentTimer = ProjectTimesheet::where('task_id', $task->id)
            ->where('user_id', auth()->id())
            ->running()
            ->latest('started_at')
            ->first();

        return view('projects::tasks.edit', array_merge(
            [
                'project' => $project,
                'task' => $task,
                'currentTimer' => $currentTimer,
            ],
            $this->formData($project)
        ));
    }

    public function update(TaskUpdate $request, int $id): Response|mixed
    {
        $task = ProjectTask::with('project')->findOrFail($id);
        $project = $this->findProject($task->project_id);
        $oldStatus = $task->status;

        $task->update([
            'milestone_id' => $request->get('milestone_id') ?: null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'assignee_id' => $request->get('assignee_id') ?: null,
            'priority' => $request->get('priority'),
            'status' => $request->get('status'),
            'estimated_hours' => $request->get('estimated_hours'),
            'position' => $request->get('position', $task->position),
        ]);

        $action = $oldStatus !== $task->status ? 'task_status_changed' : 'task_updated';

        ProjectActivity::log($project, $action, 'task', $task->id, $oldStatus !== $task->status
            ? trans('projects::general.activity.task_status_changed', ['name' => $task->name, 'status' => $task->status_label])
            : trans('projects::general.activity.task_updated', ['name' => $task->name]));

        flash(trans('messages.success.updated', ['type' => $task->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']);
    }

    public function destroy(int $id): Response|mixed
    {
        $task = ProjectTask::with('project')->findOrFail($id);
        $project = $this->findProject($task->project_id);
        $name = $task->name;

        $task->delete();

        ProjectActivity::log($project, 'task_deleted', 'task', $id, trans('projects::general.activity.task_deleted', [
            'name' => $name,
        ]));

        flash(trans('messages.success.deleted', ['type' => $name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']);
    }

    public function transition(Request $request, int $projectId, int $id): Response|mixed
    {
        $project = $this->findProject($projectId);
        $task = $project->tasks()->findOrFail($id);

        $request->validate([
            'status' => 'required|in:todo,in_progress,review,done',
        ]);

        $task->update([
            'status' => $request->get('status'),
        ]);

        ProjectActivity::log($project, 'task_status_changed', 'task', $task->id, trans('projects::general.activity.task_status_changed', [
            'name' => $task->name,
            'status' => $task->status_label,
        ]));

        flash(trans('messages.success.updated', ['type' => $task->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']);
    }

    protected function findProject(int $id): Project
    {
        return Project::where('company_id', company_id())->findOrFail($id);
    }

    protected function formData(Project $project): array
    {
        $contacts = Contact::where('company_id', company_id())->customer()->orderBy('name')->pluck('name', 'id')->prepend(trans('general.none'), '');
        $users = User::enabled()->orderBy('name')->pluck('name', 'id');
        $employees = Employee::where('company_id', company_id())->with('contact')->get()->pluck('name', 'id');

        return [
            'contacts' => $contacts,
            'users' => $users,
            'statuses' => [
                'active' => trans('projects::general.statuses.active'),
                'completed' => trans('projects::general.statuses.completed'),
                'on_hold' => trans('projects::general.statuses.on_hold'),
                'cancelled' => trans('projects::general.statuses.cancelled'),
            ],
            'billingTypes' => [
                'project_hours' => trans('projects::general.billing_types.project_hours'),
                'task_hours' => trans('projects::general.billing_types.task_hours'),
                'fixed_rate' => trans('projects::general.billing_types.fixed_rate'),
            ],
            'memberRoles' => [
                'manager' => trans('projects::general.member_roles.manager'),
                'member' => trans('projects::general.member_roles.member'),
            ],
            'selectedMembers' => $project->members()->pluck('role', 'user_id')->toArray(),
            'milestonesForSelect' => $project->milestones()->orderBy('position')->pluck('name', 'id')->prepend(trans('projects::general.no_milestone'), ''),
            'employees' => $employees,
            'taskStatuses' => [
                'todo' => trans('projects::general.task_statuses.todo'),
                'in_progress' => trans('projects::general.task_statuses.in_progress'),
                'review' => trans('projects::general.task_statuses.review'),
                'done' => trans('projects::general.task_statuses.done'),
            ],
            'priorities' => [
                'low' => trans('projects::general.priorities.low'),
                'medium' => trans('projects::general.priorities.medium'),
                'high' => trans('projects::general.priorities.high'),
                'critical' => trans('projects::general.priorities.critical'),
            ],
        ];
    }
}
