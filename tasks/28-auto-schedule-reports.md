# Task 28: Auto-Schedule Reports Module

## Context
Requires DoubleEntry for financial reports. Final module.

## Objective
Schedule automatic generation and delivery of any report.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/AutoScheduleReports/`

### 2. Database
- `report_schedules` — id, company_id, report_type (pnl/balance_sheet/trial_balance/cash_flow/ar_aging/ap_aging/budget_variance/custom), frequency (daily/weekly/monthly/quarterly/annually), next_run (datetime), recipients_json (array of emails), format (pdf/csv/excel), date_range_type (previous_month/previous_quarter/ytd/custom), custom_date_from, custom_date_to, enabled, created_at, updated_at
- `report_schedule_runs` — id, schedule_id, ran_at, file_path, status (success/failed), error_message (nullable), emailed_at (nullable)

### 3. Features
- Schedule any report: P&L, Balance Sheet, Trial Balance, Cash Flow, AR/AP Aging, Budget Variance
- Frequency: daily, weekly, monthly, quarterly, annually
- Auto-generate report as PDF/CSV on schedule
- Email to specified recipients
- Webhook delivery (POST file to URL — for Bookie → Discord)
- Schedule management UI: list, create, edit, enable/disable, delete
- Execution history: list of past runs with download link and status
- Manual trigger: run any schedule on demand

### 4. Artisan Command
`auto-reports:run` — checks for due schedules, generates reports, sends emails. Register in Laravel scheduler to run every hour.

### 5. Sidebar
Add "Scheduled Reports" under Reports section.

## Verification
1. Schedule monthly P&L report to email
2. Manually trigger → report generates as PDF
3. Email sent with PDF attachment
4. Execution log shows successful run with download link
5. Disable schedule → doesn't run
6. Next run date auto-calculates after each run

## Commit Message
`feat(modules): auto-schedule reports with email delivery and execution history`
