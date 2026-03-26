# Task 24: Budgets Module

## Context
Requires DoubleEntry for COA accounts and journal actuals.

## Objective
Budget planning by account with variance tracking against actual journal entries.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Budgets/`

### 2. Database
- `budgets` — id, company_id, name, period_start, period_end, status (draft/active/closed), created_at, updated_at
- `budget_lines` — id, budget_id, account_id (FK double_entry_accounts), amount (decimal 15,4)

### 3. Features
- Budget CRUD: name, period (monthly/quarterly/annual with start/end dates)
- Budget lines: select COA account, enter planned amount
- Budget vs actual report: for each account, show budgeted amount vs actual (from journal entries), variance ($), variance (%)
- Over-budget highlighting (red when actual > budget)
- Copy budget from previous period (pre-fill amounts)
- Multiple budget scenarios (optional: optimistic/realistic/pessimistic)
- Budget dashboard widget: summary chart, top variances
- Visual: budget vs actual bar chart
- Export report to PDF/CSV

### 4. Sidebar
Add "Budgets" under Reports or DoubleEntry section.

## Verification
1. Create annual budget with amounts for 10 accounts
2. View variance report → shows actual from journal entries vs budgeted
3. Over-budget accounts highlighted in red
4. Copy budget to new period → amounts pre-filled
5. Chart renders budget vs actual visually

## Commit Message
`feat(modules): budgets with variance tracking and reporting`
