<?php

namespace Modules\Employees\Http\Controllers\Api;

use App\Abstracts\Http\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Employees\Models\Department;

class Departments extends Controller
{
    public function index(): JsonResponse
    {
        $departments = Department::where('company_id', company_id())
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }

    public function show(int $id): JsonResponse
    {
        $department = Department::where('company_id', company_id())
            ->withCount('employees')
            ->with('manager.contact')
            ->findOrFail($id);

        return response()->json($department);
    }
}
