<?php

namespace Modules\Employees\Http\Controllers\Api;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employees\Models\Employee;

class Employees extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::where('company_id', company_id())
            ->with(['contact', 'department']);

        if ($request->filled('status')) {
            $query->status($request->get('status'));
        }

        if ($request->filled('department_id')) {
            $query->department($request->get('department_id'));
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($employees);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'type' => 'required|in:full_time,part_time,contractor,seasonal',
            'classification' => 'required|in:w2,1099',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:hourly,weekly,biweekly,monthly,yearly',
        ]);

        $employee = Employee::create([
            'company_id' => company_id(),
            'contact_id' => $request->get('contact_id'),
            'department_id' => $request->get('department_id'),
            'type' => $request->get('type'),
            'classification' => $request->get('classification'),
            'hire_date' => $request->get('hire_date'),
            'salary' => $request->get('salary'),
            'salary_type' => $request->get('salary_type'),
            'status' => 'active',
        ]);

        $employee->load(['contact', 'department']);

        return response()->json($employee, 201);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::where('company_id', company_id())
            ->with(['contact', 'department', 'documents'])
            ->findOrFail($id);

        return response()->json($employee);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($id);

        $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'type' => 'nullable|in:full_time,part_time,contractor,seasonal',
            'classification' => 'nullable|in:w2,1099',
            'status' => 'nullable|in:active,inactive,terminated',
            'salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:hourly,weekly,biweekly,monthly,yearly',
        ]);

        $employee->update($request->only([
            'department_id', 'type', 'classification', 'status',
            'salary', 'salary_type', 'hire_date', 'notes',
        ]));

        $employee->load(['contact', 'department']);

        return response()->json($employee);
    }
}
