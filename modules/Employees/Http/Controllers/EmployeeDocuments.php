<?php

namespace Modules\Employees\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;

class EmployeeDocuments extends Controller
{
    public function store(int $employeeId, Request $request): Response
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($employeeId);

        $request->validate([
            'file' => 'required|file|max:10240',
            'name' => 'required|string|max:255',
            'type' => 'required|in:w9,insurance,license,agreement,other',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('employees/documents/' . $employee->id, 'public');

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'name' => $request->get('name'),
            'file_path' => $path,
            'type' => $request->get('type'),
            'uploaded_at' => now(),
            'notes' => $request->get('notes'),
        ]);

        $message = trans('messages.success.added', ['type' => trans('employees::general.document')]);

        flash($message)->success();

        return redirect()->route('employees.employees.show', $employee->id);
    }

    public function download(int $employeeId, int $documentId): Response
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($employeeId);

        $document = EmployeeDocument::where('employee_id', $employee->id)->findOrFail($documentId);

        return Storage::disk('public')->download($document->file_path, $document->name);
    }

    public function destroy(int $employeeId, int $documentId): Response
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($employeeId);

        $document = EmployeeDocument::where('employee_id', $employee->id)->findOrFail($documentId);

        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        $message = trans('messages.success.deleted', ['type' => trans('employees::general.document')]);

        flash($message)->success();

        return redirect()->route('employees.employees.show', $employee->id);
    }
}
