# Task 06: Employees & Departments Module

## Context
Foundation HR module. Required by Payroll, Expense Claims, and Appointments.

## Objective
Build the Employees module with department management, employee profiles, salary info, document storage, and 1099/W-2 classification.

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/Employees/` with standard structure.

### 2. Database Migrations

**Table: `departments`**
- id, company_id, name, description (text nullable), manager_id (FK employees nullable), created_at, updated_at

**Table: `employees`**
- id, company_id, contact_id (FK to contacts — links to Akaunting core Contact), department_id (FK nullable), user_id (FK nullable — for login access), photo_path (varchar nullable), hire_date (date nullable), birthday (date nullable), salary (decimal 15,4 nullable), salary_type (enum: 'hourly', 'weekly', 'biweekly', 'monthly', 'yearly' nullable), bank_name (varchar nullable), bank_account (varchar nullable — encrypted), bank_routing (varchar nullable — encrypted), type (enum: 'full_time', 'part_time', 'contractor', 'seasonal'), classification (enum: 'w2', '1099'), status (enum: 'active', 'inactive', 'terminated'), terminated_at (date nullable), notes (text nullable), created_at, updated_at, deleted_at

**Table: `employee_documents`**
- id, employee_id, name (varchar), file_path (varchar), type (varchar — 'w9', 'insurance', 'license', 'agreement', 'other'), uploaded_at, notes (text nullable)

### 3. Models
- `Models/Department.php` — belongs to Company, has many Employees, belongs to manager (Employee)
- `Models/Employee.php` — belongs to Company, belongs to Department, belongs to Contact (Akaunting core), has many Documents, soft deletes
- `Models/EmployeeDocument.php` — belongs to Employee

### 4. Controllers
- `Http/Controllers/Departments.php` — CRUD with employee count per department
- `Http/Controllers/Employees.php` — Full CRUD:
  - index: filterable list (by department, status, type, classification)
  - create: form with all fields, department selector, contact selector (or create new contact)
  - store: validate, create contact if needed, create employee
  - show: profile view with all details, documents list, employment history
  - edit/update: modify any field
  - destroy: soft delete (set status to terminated)
- `Http/Controllers/EmployeeDocuments.php` — Upload/download/delete documents per employee

### 5. Views
- `views/departments/index.blade.php` — Table with name, employee count, manager
- `views/departments/create.blade.php`, `edit.blade.php` — Form
- `views/employees/index.blade.php` — Table/card view with photo, name, department, type, status, filters
- `views/employees/create.blade.php` — Multi-section form (personal, employment, salary, bank)
- `views/employees/show.blade.php` — Profile page with tabs (Details, Documents, History)
- `views/employees/edit.blade.php` — Edit form
- Employee directory with search

### 6. Sidebar
Add "HR" section to sidebar:
- Employees
- Departments

### 7. Dashboard Widget
Employee summary widget: total headcount, by department, by type, recent hires.

### 8. API Endpoints
Expose REST API for Bookie integration:
- `GET /api/employees` — list employees
- `POST /api/employees` — create employee
- `GET /api/employees/{id}` — get employee
- `PATCH /api/employees/{id}` — update
- `GET /api/departments` — list departments

## Verification
1. Create department "Masonry" — shows in list
2. Create employee "Carlos Cruz" as contractor, 1099, assigned to Masonry — shows in directory
3. Upload W-9 document to employee — file stored and accessible
4. Filter employees by department — only that department's employees shown
5. Terminate employee — status changes, soft deleted
6. Employee links to Akaunting Contact (vendor) for payment tracking
7. API endpoints return correct JSON

## Commit Message
`feat(modules): employees and departments with document management`
