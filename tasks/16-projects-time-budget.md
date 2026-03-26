# Task 16: Projects — Time Tracking + Budget vs Actual

## Context
Projects module exists from Task 15. Add time tracking and budget analysis.

## Objective
Start/stop timers on tasks, manual timesheet entry, budget vs actual reports.

## What to Build

### 1. Database
- `project_timesheets` — id, task_id, user_id, started_at (datetime), ended_at (datetime nullable), hours (decimal 8,2), billable (boolean default true), description (text nullable), created_at, updated_at

### 2. Time Tracking
- Start/stop timer button on task detail
- Manual timesheet entry: select task, enter hours, date, description
- Timesheet list per project: user, task, hours, date
- Running timer indicator in UI
- Auto-calculate hours from start/end times

### 3. Project P&L
`Http/Controllers/ProjectReports.php`:
- Revenue: sum of linked invoices
- Costs: sum of linked bills + labor cost (timesheet hours × billing rate)
- Profit: revenue - costs
- Budget vs actual: compare planned budget to actual costs
- Variance: absolute and percentage

### 4. Budget Dashboard
- Budget burn rate chart (cumulative cost over time vs budget line)
- Remaining budget
- Projected completion cost (extrapolate from burn rate)
- Over-budget alerts

### 5. Cash Flow Statement per Project
- Inflows (invoice payments received) vs Outflows (bill payments made)
- By month

### 6. Views
- Timesheet tab on project dashboard
- Budget tab with charts and variance table
- P&L report page per project

## Verification
1. Start timer on task → stop after 30 seconds → timesheet entry created with correct duration
2. Manual timesheet entry → shows in list
3. Project P&L shows correct revenue, costs, profit
4. Budget vs actual shows variance correctly
5. Budget burn chart renders

## Commit Message
`feat(modules): project time tracking, budget analysis, and P&L reports`
