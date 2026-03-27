<?php

namespace Modules\Employees\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Employees\Http\Requests\DepartmentStore;
use Modules\Employees\Http\Requests\DepartmentUpdate;
use Modules\Employees\Models\Department;
use Modules\Employees\Models\Employee;

class Departments extends Controller
{
    public function index(): Response
    {
        $departments = Department::where('company_id', company_id())
            ->withCount('employees')
            ->with('manager.contact')
            ->orderBy('name')
            ->get();

        return $this->response('employees::departments.index', compact('departments'));
    }

    public function create(): Response
    {
        $managers = Employee::where('company_id', company_id())
            ->active()
            ->with('contact')
            ->get()
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        return view('employees::departments.create', compact('managers'));
    }

    public function store(DepartmentStore $request): Response
    {
        $department = Department::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'manager_id' => $request->get('manager_id') ?: null,
        ]);

        $message = trans('messages.success.added', ['type' => $department->name]);

        flash($message)->success();

        return redirect()->route('employees.departments.index');
    }

    public function edit(int $id): Response
    {
        $department = Department::where('company_id', company_id())->findOrFail($id);

        $managers = Employee::where('company_id', company_id())
            ->active()
            ->with('contact')
            ->get()
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        return view('employees::departments.edit', compact('department', 'managers'));
    }

    public function update(int $id, DepartmentUpdate $request): Response
    {
        $department = Department::where('company_id', company_id())->findOrFail($id);

        $department->update([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'manager_id' => $request->get('manager_id') ?: null,
        ]);

        $message = trans('messages.success.updated', ['type' => $department->name]);

        flash($message)->success();

        return redirect()->route('employees.departments.index');
    }

    public function destroy(int $id): Response
    {
        $department = Department::where('company_id', company_id())->findOrFail($id);

        $department->delete();

        $message = trans('messages.success.deleted', ['type' => $department->name]);

        flash($message)->success();

        return redirect()->route('employees.departments.index');
    }
}
