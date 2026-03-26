# Task 03: General Ledger + Trial Balance Views

## Context
DoubleEntry module has COA (Task 01) and Journal Engine (Task 02). Now build the General Ledger view and Trial Balance report.

## Objective
Create the General Ledger page (all journal lines by account with running balances) and Trial Balance report (debit/credit totals per account).

## What to Build

### 1. General Ledger Controller
`Http/Controllers/GeneralLedger.php`:
- `index()` — Show all journal lines grouped by account
  - Filters: date range, specific account, accounting basis (cash/accrual)
  - For each account: list of journal lines with date, reference, description, debit, credit, running balance
  - Running balance calculates from opening balance + all prior entries
  - Export to CSV and PDF

### 2. Trial Balance Controller
`Http/Controllers/TrialBalance.php`:
- `index()` — Show all accounts with total debits and total credits for the period
  - Filters: as-of date, date range
  - Columns: Account Code, Account Name, Debit, Credit
  - Bottom row: Total Debits, Total Credits (must be equal)
  - Grouped by account type (Asset, Liability, Equity, Income, Expense)
  - Export to CSV and PDF

### 3. Views
- `views/general-ledger/index.blade.php`:
  - Filter bar: date range picker, account selector (dropdown), basis toggle
  - Expandable sections per account showing journal lines
  - Running balance column
  - Totals per account
  - Export buttons (CSV, PDF)

- `views/trial-balance/index.blade.php`:
  - Filter bar: as-of date
  - Table: Code | Account Name | Debit | Credit
  - Grouped by type with subtotals
  - Grand total row (should balance)
  - Export buttons

### 4. PDF Generation
Use Akaunting's existing PDF generation (likely DomPDF via Laravel). Create PDF views:
- `views/general-ledger/pdf.blade.php`
- `views/trial-balance/pdf.blade.php`

### 5. Routes
```php
Route::get('general-ledger', 'GeneralLedger@index')->name('double-entry.general-ledger');
Route::get('general-ledger/export/{format}', 'GeneralLedger@export')->name('double-entry.general-ledger.export');
Route::get('trial-balance', 'TrialBalance@index')->name('double-entry.trial-balance');
Route::get('trial-balance/export/{format}', 'TrialBalance@export')->name('double-entry.trial-balance.export');
```

### 6. Helper: Account Balance Calculator
Create `Services/AccountBalanceService.php`:
- `getBalance($accountId, $asOfDate, $basis)` — Calculate balance from opening + journal lines
- `getBalancesByType($companyId, $dateFrom, $dateTo, $basis)` — Grouped totals for reports
- `getRunningBalance($accountId, $dateFrom, $dateTo)` — Array of running balances per entry
- Account balance rules:
  - Assets & Expenses: normal debit balance (debits increase, credits decrease)
  - Liabilities, Equity & Income: normal credit balance (credits increase, debits decrease)

## Verification
1. General Ledger shows all journal entries per account with correct running balances
2. Filtering by date range shows only entries in that period
3. Filtering by specific account shows only that account's entries
4. Trial Balance shows all accounts with correct debit/credit totals
5. Trial Balance totals are equal (debits = credits)
6. CSV export downloads correctly formatted file
7. PDF export generates readable document

## Commit Message
`feat(modules): general ledger and trial balance views with export`
