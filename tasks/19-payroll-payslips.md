# Task 19: Payroll — Payslip Generation + Benefits/Deductions

## Context
Payroll module has calendars and runs from Task 18. Add payslips.

## Objective
Generate PDF payslips per employee per payroll run with itemized benefits and deductions.

## What to Build

### 1. Database
- `payslips` — id, payroll_run_id, employee_id, gross (decimal), total_benefits (decimal), total_deductions (decimal), net (decimal), pdf_path (nullable), emailed_at (nullable), created_at
- `payslip_items` — id, payslip_id, pay_item_id, type (benefit/deduction), name, amount, is_percentage, percentage_of

### 2. Payslip Generation
- Auto-create payslips when payroll run is processed
- Per employee: list all assigned benefits and deductions with amounts
- Calculate: Gross + Benefits - Deductions = Net
- Generate PDF payslip with: employee info (name, department, tax number), period dates, gross breakdown, benefits list, deductions list, net pay, bank details
- Print-ready format

### 3. Features
- View payslip detail
- Download PDF
- Email payslip to employee
- Bulk email all payslips for a run
- Payslip history per employee
- Employee self-service: view own payslips (if portal access enabled)

### 4. PDF Template
Professional payslip layout with company branding, employee details, itemized earnings and deductions.

## Verification
1. Process payroll run → payslips auto-generated for each employee
2. Payslip shows correct gross, benefits, deductions, net
3. PDF downloads with proper formatting
4. Email payslip to employee works
5. Payslip history shows all past payslips per employee

## Commit Message
`feat(modules): payslip generation with PDF, email, and benefits/deductions detail`
