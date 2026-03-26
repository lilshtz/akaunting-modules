# Task 04: Balance Sheet + Profit & Loss Reports

## Context
DoubleEntry module has COA, Journals, General Ledger, and Trial Balance. Now build the two flagship financial reports.

## Objective
Balance Sheet (assets/liabilities/equity as of a date) and Profit & Loss (income vs expenses for a period).

## What to Build

### 1. Balance Sheet Controller
`Http/Controllers/BalanceSheet.php`:
- `index()` — Render Balance Sheet
  - As-of date selector (default: today)
  - Comparative toggle: show previous period side-by-side
  - Sections: Assets (Current/Non-Current), Liabilities (Current/Long-Term), Equity
  - Hierarchical: follows COA parent/child structure with indentation
  - Totals: Total Assets, Total Liabilities, Total Equity
  - Validation: Assets = Liabilities + Equity (accounting equation)
  - Accounting basis selector (cash/accrual)
- `export($format)` — PDF or CSV

### 2. Profit & Loss Controller
`Http/Controllers/ProfitLoss.php`:
- `index()` — Render P&L
  - Date range selector (default: current month)
  - Period breakdown: Monthly, Quarterly, Annual columns
  - Sections: Income (by category/account), Expenses (by category/account)
  - Net Profit/Loss = Total Income - Total Expenses
  - Comparative: this period vs same period last year
  - Accounting basis selector
  - Percentage column (each line as % of total income)
- `export($format)` — PDF or CSV

### 3. Views
- `views/balance-sheet/index.blade.php`:
  - Header: company name, "Balance Sheet", as-of date
  - Three sections with hierarchical account listing
  - Subtotals per section, grand totals
  - Comparative columns if enabled
  - Export buttons

- `views/profit-loss/index.blade.php`:
  - Header: company name, "Profit & Loss", date range
  - Income section with account-level detail
  - Expense section with account-level detail
  - Gross Profit line (if COGS accounts exist)
  - Net Profit/Loss highlighted
  - Period columns if breakdown selected
  - Export buttons

- PDF views for both reports

### 4. Double-Entry Dashboard Widget
Create `Widgets/DoubleEntryDashboard.php`:
- Income vs Expense chart (bar chart, last 12 months)
- Account balances summary (top 5 by balance)
- Recent journal entries (last 10)
- Quick links to reports
- Register widget in module.json

### 5. Routes
```php
Route::get('balance-sheet', 'BalanceSheet@index')->name('double-entry.balance-sheet');
Route::get('balance-sheet/export/{format}', 'BalanceSheet@export');
Route::get('profit-loss', 'ProfitLoss@index')->name('double-entry.profit-loss');
Route::get('profit-loss/export/{format}', 'ProfitLoss@export');
```

### 6. Update Sidebar
Update Event.php to make all sidebar links active (previously were placeholder links in Task 01).

## Verification
1. Balance Sheet shows correct totals — Assets = Liabilities + Equity
2. Balance Sheet respects as-of date (excludes future entries)
3. Balance Sheet shows hierarchical account structure (parent → children indented)
4. P&L shows income minus expenses = net profit for the period
5. P&L monthly breakdown shows correct per-month figures
6. Comparative view shows this year vs last year side by side
7. PDF exports are properly formatted and readable
8. Dashboard widget renders charts and data correctly
9. All sidebar links now navigate to working pages

## Commit Message
`feat(modules): balance sheet, profit & loss reports, dashboard widget`
