# Task 27: Appointments & Leave Module

## Context
Requires Employees for leave management.

## Objective
Appointment scheduling and employee leave request management.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Appointments/`

### 2. Database
- `appointments` — id, company_id, contact_id (FK nullable), user_id (FK), date, start_time (time), end_time (time), location, status (scheduled/completed/cancelled/no_show), notes, reminder_sent (boolean default false), created_at, updated_at
- `appointment_forms` — id, company_id, name, fields_json, public_link (unique token), enabled, created_at, updated_at
- `leave_requests` — id, company_id, employee_id (FK), approver_id (FK), type (vacation/sick/personal/other), start_date, end_date, days (decimal), status (pending/approved/refused), reason (text nullable), approved_at, refused_at, refusal_reason, created_at

### 3. Features
- Appointment CRUD: schedule with date, time, location, assigned user
- Calendar view (monthly/weekly/daily)
- Appointment forms: create customizable public forms for customers to book
- Customer self-scheduling via public link
- Reminder emails (day before)
- Leave requests: employee submits, approver reviews
- Leave balance tracking per employee per year
- Leave types: vacation, sick, personal (customizable)
- Reports: appointment history, leave summary per employee

### 4. Sidebar
Add "Appointments" and "Leave" under HR section.

## Verification
1. Create appointment → shows on calendar
2. Customer books via public form link → appointment created
3. Employee submits leave request → approver notified
4. Approve leave → deducted from balance
5. Calendar shows appointments and approved leave

## Commit Message
`feat(modules): appointments with calendar and leave management`
