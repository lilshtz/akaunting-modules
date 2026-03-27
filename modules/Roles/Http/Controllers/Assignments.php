<?php

namespace Modules\Roles\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Response;
use Modules\Roles\Http\Requests\RoleAssignmentStore;
use Modules\Roles\Models\Role;
use Modules\Roles\Models\UserCompanyRole;
use Modules\Roles\Services\RoleTemplateService;

class Assignments extends Controller
{
    public function __construct(protected \Modules\Roles\Services\PermissionResolver $resolver, protected RoleTemplateService $templates)
    {
    }

    public function index(): Response|mixed
    {
        $companyId = company_id();

        $this->templates->ensureDefaults($companyId);

        $users = User::whereHas('companies', fn ($query) => $query->where('companies.id', $companyId))
            ->with(['roles'])
            ->orderBy('name')
            ->get();

        $assignments = UserCompanyRole::with('role')
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('user_id');

        $roles = Role::where(function ($query) use ($companyId) {
            $query->whereHas('modulePermissions', fn ($sub) => $sub->where('company_id', $companyId))
                ->orWhereIn('name', ['admin', 'module-manager', 'module-accountant', 'module-employee', 'module-customer']);
        })->orderBy('display_name')->get();

        return view('roles::roles.assignments', compact('users', 'assignments', 'roles'));
    }

    public function store(RoleAssignmentStore $request): Response|mixed
    {
        $this->resolver->assignRoleToUser(
            $request->integer('user_id'),
            $request->integer('role_id'),
            company_id()
        );

        flash(trans('roles::general.messages.assignment_saved'))->success();

        return redirect()->route('roles.assignments.index');
    }

    public function destroy(int $userId): Response|mixed
    {
        $this->resolver->removeAssignment($userId, company_id());

        flash(trans('messages.success.deleted', ['type' => trans('roles::general.assignment')]))->success();

        return redirect()->route('roles.assignments.index');
    }
}
