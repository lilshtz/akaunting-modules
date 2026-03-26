# Task 18: Payroll Module — Pay Calendars + Payroll Runs

## Context
Requires Employees module. Phase 1: calendars and runs. Phase 2 (Task 19): payslips.

## Objective
Create pay calendars, run payroll for employees.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Payroll/`

### 2. Database
- `pay_items` — id, company_id, type (benefit/deduction), name, default_amount (decimal nullable), is_percentage (boolean default false), enabled, created_at, updated_at
- `pay_calendars` — id, company_id, name, frequency (weekly/biweekly/monthly/custom), start_date, next_run_date, enabled, created_at, updated_at
- `pay_calendar_employees` — pay_calendar_id, employee_id
- `payroll_runs` — id, company_id, pay_calendar_id, period_start, period_end, status (draft/review/approved/processed/completed), total_gross (decimal), total_deductions (decimal), total_net (decimal), approved_by (FK nullable), processed_at (nullable), created_at, updated_at

### 3. Controllers
- `PayItems` — CRUD for benefit and deduction types
- `PayCalendars` — CRUD, assign employees, set frequency
- `PayrollRuns` — Create run from calendar → Review employee pay → Approve → Process
  - Create: auto-populate employees from calendar, calculate gross from salary
  - Review: show all employees with gross, benefits, deductions, net
  - Edit individual employee amounts if needed
  - Approve: lock in amounts
  - Process: mark as processed, auto-post journal entries (salary expense → bank)

### 4. Settings
Payroll settings page: default pay items, default benefits/deductions for new employees.

### 5. Sidebar
Add "Payroll" under HR section with sub-items: Pay Items, Pay Calendars, Payroll Runs.

## Verification
1. Create pay items: Bonus (benefit), Tax (deduction)
2. Create monthly pay calendar, assign 3 employees
3. Run payroll → shows employees with calculated gross, deductions, net
4. Approve → process → journal entries created
5. Payroll run history shows completed runs

## Commit Message
`feat(modules): payroll with pay calendars, pay items, and payroll runs`
