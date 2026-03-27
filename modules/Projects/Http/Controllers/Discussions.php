<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Projects\Http\Requests\DiscussionStore;
use Modules\Projects\Http\Requests\DiscussionUpdate;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectActivity;
use Modules\Projects\Models\ProjectDiscussion;

class Discussions extends Controller
{
    public function store(DiscussionStore $request, int $projectId): Response
    {
        $project = $this->findProject($projectId);

        $discussion = $project->discussionEntries()->create([
            'user_id' => auth()->id(),
            'parent_id' => $request->get('parent_id') ?: null,
            'body' => $request->get('body'),
        ]);

        ProjectActivity::log($project, 'discussion_posted', 'discussion', $discussion->id, trans('projects::general.activity.discussion_posted'));

        flash(trans('messages.success.added', ['type' => trans('projects::general.discussion')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'discussions']);
    }

    public function update(DiscussionUpdate $request, int $id): Response
    {
        $discussion = $this->findDiscussion($id);
        $project = $this->findProject($discussion->project_id);

        $discussion->update([
            'body' => $request->get('body'),
        ]);

        flash(trans('messages.success.updated', ['type' => trans('projects::general.discussion')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'discussions']);
    }

    public function destroy(int $id): Response
    {
        $discussion = $this->findDiscussion($id);
        $project = $this->findProject($discussion->project_id);

        $discussion->delete();

        flash(trans('messages.success.deleted', ['type' => trans('projects::general.discussion')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'discussions']);
    }

    protected function findProject(int $id): Project
    {
        return Project::where('company_id', company_id())->findOrFail($id);
    }

    protected function findDiscussion(int $id): ProjectDiscussion
    {
        return ProjectDiscussion::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->where('company_id', company_id()))
            ->findOrFail($id);
    }
}
