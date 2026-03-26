<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use App\Models\Document\Document;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Employees\Models\Employee;
use Modules\Projects\Http\Requests\ProjectStore;
use Modules\Projects\Http\Requests\ProjectUpdate;
use Modules\Projects\Http\Requests\TransactionStore;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectActivity;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Models\ProjectTransaction;
use Modules\Projects\Models\ProjectTimesheet;
use Modules\Projects\Services\ProjectReportService;

class Projects extends Controller
{
    public function index(Request $request): Response|mixed
    {
        $query = Project::where('company_id', company_id())
            ->with(['contact', 'tasks', 'milestones']);

        if ($request->filled('status')) {
            $query->status($request->get('status'));
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->integer('contact_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $projects = $query->latest()->paginate(20);

        $contacts = Contact::where('company_id', company_id())
            ->customer()
            ->orderBy('name')
            ->pluck('name', 'id');

        $statuses = $this->statusOptions();

        return $this->response('projects::projects.index', compact('projects', 'contacts', 'statuses'));
    }

    public function create(): Response|mixed
    {
        return view('projects::projects.create', $this->formData());
    }

    public function store(ProjectStore $request): Response|mixed
    {
        $project = Project::create([
            'company_id' => company_id(),
            'contact_id' => $request->get('contact_id') ?: null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'status' => $request->get('status'),
            'billing_type' => $request->get('billing_type'),
            'billing_rate' => $request->get('billing_rate'),
            'budget' => $request->get('budget'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ]);

        $this->syncMembers($project, $request);

        ProjectActivity::log($project, 'project_created', 'project', $project->id, trans('projects::general.activity.project_created', [
            'name' => $project->name,
        ]));

        flash(trans('messages.success.added', ['type' => $project->name]))->success();

        return redirect()->route('projects.projects.show', $project->id);
    }

    public function show(Request $request, int $id, ProjectReportService $reportService): Response|mixed
    {
        $project = $this->findProject($id, [
            'contact',
            'members.user',
            'milestones.tasks.assignee',
            'transactions.document',
            'discussions.user',
            'discussions.replies.user',
            'activities.user',
        ]);

        $project->load([
            'tasks' => function ($query) {
                $query->with(['assignee', 'milestone'])
                    ->withSum('timesheets', 'hours')
                    ->orderBy('position');
            },
        ]);

        $tab = $request->get('tab', 'overview');
        $report = $reportService->build($project);

        $taskColumns = [
            'todo' => $project->tasks->where('status', 'todo'),
            'in_progress' => $project->tasks->where('status', 'in_progress'),
            'review' => $project->tasks->where('status', 'review'),
            'done' => $project->tasks->where('status', 'done'),
        ];

        $timesheets = $project->timesheets()
            ->with(['task', 'user'])
            ->latest('started_at')
            ->get();

        $activeTimers = ProjectTimesheet::with(['task', 'user'])
            ->running()
            ->whereHas('task.project', fn ($query) => $query->where('id', $project->id)->where('company_id', company_id()))
            ->latest('started_at')
            ->get();

        $activeTimerMap = $activeTimers->keyBy('task_id');
        $currentUserActiveTimer = $activeTimers->firstWhere('user_id', auth()->id());

        $transactionSummary = [
            'invoice_count' => $project->transactions->where('document_type', 'invoice')->count(),
            'bill_count' => $project->transactions->where('document_type', 'bill')->count(),
            'invoice_total' => $project->transactions->where('document_type', 'invoice')->sum(fn ($item) => (float) ($item->document?->amount ?? 0)),
            'bill_total' => $project->transactions->where('document_type', 'bill')->sum(fn ($item) => (float) ($item->document?->amount ?? 0)),
        ];

        return view('projects::projects.show', array_merge(
            [
                'project' => $project,
                'tab' => $tab,
                'taskColumns' => $taskColumns,
                'transactionSummary' => $transactionSummary,
                'timesheets' => $timesheets,
                'activeTimers' => $activeTimers,
                'activeTimerMap' => $activeTimerMap,
                'currentUserActiveTimer' => $currentUserActiveTimer,
                'report' => $report,
            ],
            $this->formData($project),
            $this->nestedFormData($project)
        ));
    }

    public function edit(int $id): Response|mixed
    {
        $project = $this->findProject($id, ['members']);

        return view('projects::projects.edit', array_merge(
            ['project' => $project],
            $this->formData($project)
        ));
    }

    public function update(int $id, ProjectUpdate $request): Response|mixed
    {
        $project = $this->findProject($id);

        $project->update([
            'contact_id' => $request->get('contact_id') ?: null,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'status' => $request->get('status'),
            'billing_type' => $request->get('billing_type'),
            'billing_rate' => $request->get('billing_rate'),
            'budget' => $request->get('budget'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ]);

        $this->syncMembers($project, $request);

        ProjectActivity::log($project, 'project_updated', 'project', $project->id, trans('projects::general.activity.project_updated', [
            'name' => $project->name,
        ]));

        flash(trans('messages.success.updated', ['type' => $project->name]))->success();

        return redirect()->route('projects.projects.show', $project->id);
    }

    public function destroy(int $id): Response|mixed
    {
        $project = $this->findProject($id);

        $project->delete();

        flash(trans('messages.success.deleted', ['type' => $project->name]))->success();

        return redirect()->route('projects.projects.index');
    }

    public function createTransaction(int $projectId): Response|mixed
    {
        $project = $this->findProject($projectId);

        return view('projects::projects.transactions-create', array_merge(
            ['project' => $project],
            $this->formData($project),
            $this->nestedFormData($project)
        ));
    }

    public function storeTransaction(int $projectId, TransactionStore $request): Response|mixed
    {
        $project = $this->findProject($projectId);

        $document = Document::where('company_id', company_id())
            ->where('id', $request->integer('document_id'))
            ->where('type', $request->get('document_type'))
            ->firstOrFail();

        $transaction = ProjectTransaction::firstOrCreate([
            'project_id' => $project->id,
            'document_type' => $request->get('document_type'),
            'document_id' => $document->id,
        ]);

        ProjectActivity::log($project, 'transaction_linked', 'document', $document->id, trans('projects::general.activity.transaction_linked', [
            'number' => $document->document_number ?: ('#' . $document->id),
        ]));

        flash(trans('messages.success.added', ['type' => $transaction->document_type_label]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'transactions']);
    }

    public function destroyTransaction(int $projectId, int $transactionId): Response|mixed
    {
        $project = $this->findProject($projectId);
        $transaction = $project->transactions()->findOrFail($transactionId);

        $transaction->delete();

        flash(trans('messages.success.deleted', ['type' => trans('projects::general.transaction')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'transactions']);
    }

    public function updateMembers(Request $request, int $projectId): Response|mixed
    {
        $project = $this->findProject($projectId);

        $request->validate([
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
            'member_roles' => 'nullable|array',
        ]);

        $this->syncMembers($project, $request);

        ProjectActivity::log($project, 'members_updated', 'project', $project->id, trans('projects::general.activity.members_updated'));

        flash(trans('messages.success.updated', ['type' => trans('projects::general.team_members')]))->success();

        return redirect()->route('projects.projects.show', ['project' => $project->id, 'tab' => 'overview']);
    }

    protected function findProject(int $id, array $with = []): Project
    {
        return Project::where('company_id', company_id())
            ->with($with)
            ->findOrFail($id);
    }

    protected function formData(?Project $project = null): array
    {
        $contacts = Contact::where('company_id', company_id())
            ->customer()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $users = User::enabled()
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'contacts' => $contacts,
            'users' => $users,
            'statuses' => $this->statusOptions(),
            'billingTypes' => $this->billingTypeOptions(),
            'memberRoles' => $this->memberRoleOptions(),
            'selectedMembers' => $project ? $project->members->pluck('role', 'user_id')->toArray() : [],
        ];
    }

    protected function nestedFormData(Project $project): array
    {
        $milestones = $project->milestones()->orderBy('position')->pluck('name', 'id')->prepend(trans('projects::general.no_milestone'), '');
        $employees = Employee::where('company_id', company_id())
            ->with('contact')
            ->orderBy('id')
            ->get()
            ->pluck('name', 'id');

        $documents = Document::where('company_id', company_id())
            ->whereIn('type', ['invoice', 'bill'])
            ->latest()
            ->take(100)
            ->get()
            ->groupBy('type');

        return [
            'milestonesForSelect' => $milestones,
            'employees' => $employees,
            'taskStatuses' => $this->taskStatusOptions(),
            'priorities' => $this->priorityOptions(),
            'documentsByType' => $documents,
        ];
    }

    protected function syncMembers(Project $project, Request $request): void
    {
        $memberIds = collect($request->input('member_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            ProjectMember::where('project_id', $project->id)->delete();
        } else {
            ProjectMember::where('project_id', $project->id)
                ->whereNotIn('user_id', $memberIds->all())
                ->delete();
        }

        foreach ($memberIds as $userId) {
            ProjectMember::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'user_id' => $userId,
                ],
                [
                    'role' => $request->input('member_roles.' . $userId, 'member'),
                ]
            );
        }
    }

    protected function statusOptions(): array
    {
        return [
            'active' => trans('projects::general.statuses.active'),
            'completed' => trans('projects::general.statuses.completed'),
            'on_hold' => trans('projects::general.statuses.on_hold'),
            'cancelled' => trans('projects::general.statuses.cancelled'),
        ];
    }

    protected function billingTypeOptions(): array
    {
        return [
            'project_hours' => trans('projects::general.billing_types.project_hours'),
            'task_hours' => trans('projects::general.billing_types.task_hours'),
            'fixed_rate' => trans('projects::general.billing_types.fixed_rate'),
        ];
    }

    protected function memberRoleOptions(): array
    {
        return [
            'manager' => trans('projects::general.member_roles.manager'),
            'member' => trans('projects::general.member_roles.member'),
        ];
    }

    protected function taskStatusOptions(): array
    {
        return [
            'todo' => trans('projects::general.task_statuses.todo'),
            'in_progress' => trans('projects::general.task_statuses.in_progress'),
            'review' => trans('projects::general.task_statuses.review'),
            'done' => trans('projects::general.task_statuses.done'),
        ];
    }

    protected function priorityOptions(): array
    {
        return [
            'low' => trans('projects::general.priorities.low'),
            'medium' => trans('projects::general.priorities.medium'),
            'high' => trans('projects::general.priorities.high'),
            'critical' => trans('projects::general.priorities.critical'),
        ];
    }
}
