<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Auth\User;
use Illuminate\Http\Response;
use Modules\Projects\Http\Requests\MilestoneStore;
use Modules\Projects\Http\Requests\MilestoneUpdate;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectActivity;
use Modules\Projects\Models\ProjectMilestone;

class Milestones extends Controller
{
    public function store(MilestoneStore $request, int $projectId): Response
    {
        $project = $this->findProject($projectId);

        $milestone = $project->milestones()->create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'target_date' => $request->get('target_date'),
            'completed_at' => $request->get('completed_at'),
            'position' => $request->get('position', (int) $project->milestones()->max('position') + 1),
        ]);

        ProjectActivity::log($project, 'milestone_created', 'milestone', $milestone->id, trans('projects::general.activity.milestone_created', [
            'name' => $milestone->name,
        ]));

        if ($milestone->completed_at) {
            ProjectActivity::log($project, 'milestone_completed', 'milestone', $milestone->id, trans('projects::general.activity.milestone_completed', [
                'name' => $milestone->name,
            ]));
        }

        flash(trans('messages.success.added', ['type' => $milestone->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'milestones']);
    }

    public function edit(int $id): Response
    {
        $milestone = ProjectMilestone::with('project')->findOrFail($id);
        $project = $this->findProject($milestone->project_id);

        return view('projects::milestones.edit', array_merge(
            [
                'project' => $project,
                'milestone' => $milestone,
            ],
            $this->projectFormData($project)
        ));
    }

    public function update(MilestoneUpdate $request, int $id): Response
    {
        $milestone = ProjectMilestone::with('project')->findOrFail($id);
        $project = $this->findProject($milestone->project_id);
        $wasCompleted = $milestone->completed_at !== null;

        $milestone->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'target_date' => $request->get('target_date'),
            'completed_at' => $request->get('completed_at'),
            'position' => $request->get('position', $milestone->position),
        ]);

        if (! $wasCompleted && $milestone->completed_at) {
            ProjectActivity::log($project, 'milestone_completed', 'milestone', $milestone->id, trans('projects::general.activity.milestone_completed', [
                'name' => $milestone->name,
            ]));
        } else {
            ProjectActivity::log($project, 'milestone_updated', 'milestone', $milestone->id, trans('projects::general.activity.milestone_updated', [
                'name' => $milestone->name,
            ]));
        }

        flash(trans('messages.success.updated', ['type' => $milestone->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'milestones']);
    }

    public function destroy(int $id): Response
    {
        $milestone = ProjectMilestone::with('project')->findOrFail($id);
        $project = $this->findProject($milestone->project_id);
        $name = $milestone->name;

        $milestone->delete();

        ProjectActivity::log($project, 'milestone_deleted', 'milestone', $id, trans('projects::general.activity.milestone_deleted', [
            'name' => $name,
        ]));

        flash(trans('messages.success.deleted', ['type' => $name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'milestones']);
    }

    public function complete(int $projectId, int $id): Response
    {
        $project = $this->findProject($projectId);
        $milestone = $project->milestones()->findOrFail($id);
        $completed = ! $milestone->completed_at;

        $milestone->update([
            'completed_at' => $completed ? now() : null,
        ]);

        ProjectActivity::log(
            $project,
            $completed ? 'milestone_completed' : 'milestone_updated',
            'milestone',
            $milestone->id,
            $completed
                ? trans('projects::general.activity.milestone_completed', ['name' => $milestone->name])
                : trans('projects::general.activity.milestone_updated', ['name' => $milestone->name])
        );

        flash(trans('messages.success.updated', ['type' => $milestone->name]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'milestones']);
    }

    protected function findProject(int $id): Project
    {
        return Project::where('company_id', company_id())->findOrFail($id);
    }

    protected function projectFormData(Project $project): array
    {
        $contacts = Contact::where('company_id', company_id())->customer()->orderBy('name')->pluck('name', 'id')->prepend(trans('general.none'), '');
        $users = User::enabled()->orderBy('name')->pluck('name', 'id');

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
        ];
    }
}
