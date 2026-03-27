<?php

namespace Modules\Employees\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Common\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Employees\Http\Requests\EmployeeStore;
use Modules\Employees\Http\Requests\EmployeeUpdate;
use Modules\Employees\Models\Department;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;

class Employees extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::where('company_id', company_id())
            ->with(['contact', 'department']);

        if ($request->filled('department_id')) {
            $query->department($request->get('department_id'));
        }

        if ($request->filled('status')) {
            $query->status($request->get('status'));
        }

        if ($request->filled('type')) {
            $query->type($request->get('type'));
        }

        if ($request->filled('classification')) {
            $query->classification($request->get('classification'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(25);

        $departments = Department::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id');

        $statuses = [
            'active' => trans('employees::general.statuses.active'),
            'inactive' => trans('employees::general.statuses.inactive'),
            'terminated' => trans('employees::general.statuses.terminated'),
        ];

        $types = [
            'full_time' => trans('employees::general.types.full_time'),
            'part_time' => trans('employees::general.types.part_time'),
            'contractor' => trans('employees::general.types.contractor'),
            'seasonal' => trans('employees::general.types.seasonal'),
        ];

        return $this->response('employees::employees.index', compact('employees', 'departments', 'statuses', 'types'));
    }

    public function create(): Response
    {
        $departments = Department::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $contacts = Contact::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('employees::general.create_new_contact'), '');

        $types = [
            'full_time' => trans('employees::general.types.full_time'),
            'part_time' => trans('employees::general.types.part_time'),
            'contractor' => trans('employees::general.types.contractor'),
            'seasonal' => trans('employees::general.types.seasonal'),
        ];

        $classifications = [
            'w2' => trans('employees::general.classifications.w2'),
            '1099' => trans('employees::general.classifications.1099'),
        ];

        $salaryTypes = [
            '' => trans('general.none'),
            'hourly' => trans('employees::general.salary_types.hourly'),
            'weekly' => trans('employees::general.salary_types.weekly'),
            'biweekly' => trans('employees::general.salary_types.biweekly'),
            'monthly' => trans('employees::general.salary_types.monthly'),
            'yearly' => trans('employees::general.salary_types.yearly'),
        ];

        return view('employees::employees.create', compact(
            'departments', 'contacts', 'types', 'classifications', 'salaryTypes'
        ));
    }

    public function store(EmployeeStore $request): Response
    {
        // Create new contact if no existing contact selected
        $contactId = $request->get('contact_id');

        if (! $contactId && $request->filled('contact_name')) {
            $contact = Contact::create([
                'company_id' => company_id(),
                'type' => Contact::EMPLOYEE_TYPE ?? 'employee',
                'name' => $request->get('contact_name'),
                'email' => $request->get('contact_email'),
                'enabled' => true,
            ]);
            $contactId = $contact->id;
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee = Employee::create([
            'company_id' => company_id(),
            'contact_id' => $contactId,
            'department_id' => $request->get('department_id') ?: null,
            'user_id' => $request->get('user_id') ?: null,
            'photo_path' => $photoPath,
            'hire_date' => $request->get('hire_date'),
            'birthday' => $request->get('birthday'),
            'salary' => $request->get('salary'),
            'salary_type' => $request->get('salary_type') ?: null,
            'bank_name' => $request->get('bank_name'),
            'bank_account' => $request->get('bank_account'),
            'bank_routing' => $request->get('bank_routing'),
            'type' => $request->get('type'),
            'classification' => $request->get('classification'),
            'status' => $request->get('status', 'active'),
            'notes' => $request->get('notes'),
        ]);

        $message = trans('messages.success.added', ['type' => $employee->name]);

        flash($message)->success();

        return redirect()->route('employees.employees.show', $employee->id);
    }

    public function show(int $id): Response
    {
        $employee = Employee::where('company_id', company_id())
            ->with(['contact', 'department', 'documents', 'user'])
            ->findOrFail($id);

        $documentTypes = EmployeeDocument::documentTypes();

        return view('employees::employees.show', compact('employee', 'documentTypes'));
    }

    public function edit(int $id): Response
    {
        $employee = Employee::where('company_id', company_id())
            ->with('contact')
            ->findOrFail($id);

        $departments = Department::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('general.none'), '');

        $contacts = Contact::where('company_id', company_id())
            ->orderBy('name')
            ->pluck('name', 'id');

        $types = [
            'full_time' => trans('employees::general.types.full_time'),
            'part_time' => trans('employees::general.types.part_time'),
            'contractor' => trans('employees::general.types.contractor'),
            'seasonal' => trans('employees::general.types.seasonal'),
        ];

        $classifications = [
            'w2' => trans('employees::general.classifications.w2'),
            '1099' => trans('employees::general.classifications.1099'),
        ];

        $salaryTypes = [
            '' => trans('general.none'),
            'hourly' => trans('employees::general.salary_types.hourly'),
            'weekly' => trans('employees::general.salary_types.weekly'),
            'biweekly' => trans('employees::general.salary_types.biweekly'),
            'monthly' => trans('employees::general.salary_types.monthly'),
            'yearly' => trans('employees::general.salary_types.yearly'),
        ];

        $statuses = [
            'active' => trans('employees::general.statuses.active'),
            'inactive' => trans('employees::general.statuses.inactive'),
            'terminated' => trans('employees::general.statuses.terminated'),
        ];

        return view('employees::employees.edit', compact(
            'employee', 'departments', 'contacts', 'types', 'classifications', 'salaryTypes', 'statuses'
        ));
    }

    public function update(int $id, EmployeeUpdate $request): Response
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($id);

        $data = [
            'department_id' => $request->get('department_id') ?: null,
            'user_id' => $request->get('user_id') ?: null,
            'hire_date' => $request->get('hire_date'),
            'birthday' => $request->get('birthday'),
            'salary' => $request->get('salary'),
            'salary_type' => $request->get('salary_type') ?: null,
            'bank_name' => $request->get('bank_name'),
            'bank_account' => $request->get('bank_account'),
            'bank_routing' => $request->get('bank_routing'),
            'type' => $request->get('type'),
            'classification' => $request->get('classification'),
            'status' => $request->get('status', $employee->status),
            'terminated_at' => $request->get('terminated_at'),
            'notes' => $request->get('notes'),
        ];

        if ($request->filled('contact_id')) {
            $data['contact_id'] = $request->get('contact_id');
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee->update($data);

        $message = trans('messages.success.updated', ['type' => $employee->name]);

        flash($message)->success();

        return redirect()->route('employees.employees.show', $employee->id);
    }

    public function destroy(int $id): Response
    {
        $employee = Employee::where('company_id', company_id())->findOrFail($id);

        $employee->terminate();

        $message = trans('messages.success.deleted', ['type' => $employee->name]);

        flash($message)->success();

        return redirect()->route('employees.employees.index');
    }
}
