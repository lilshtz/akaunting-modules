# Task 04: Double-Entry — Reports + Account Defaults

## Context
Chart of Accounts and Journal Entries are built. Now build the four financial reports and account defaults settings page. These are READ-ONLY views that aggregate journal data.

## Environment
- Module source: `/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/`
- Docker: `akaunting` on port 8085
- Test URL base: `http://100.83.12.126:8085/1/double-entry/`

## What to Build

### 1. AccountBalanceService (`Services/AccountBalanceService.php`)
Central service for all balance calculations. Methods:
- `getAccountBalance($accountId, $startDate = null, $endDate = null)` — net balance for one account
- `getAccountBalances($type = null, $startDate = null, $endDate = null)` — all account balances, optionally filtered by type
- `getTrialBalance($startDate = null, $endDate = null)` — array of accounts with debit/credit totals
- `getBalanceSheet($asOfDate)` — Assets, Liabilities, Equity totals and breakdown
- `getProfitAndLoss($startDate, $endDate)` — Income, Expenses, Net Profit

Balance logic:
- Assets & Expenses: normal debit balance (debits increase, credits decrease) → balance = sum(debits) - sum(credits) + opening_balance
- Liabilities, Equity & Income: normal credit balance → balance = sum(credits) - sum(debits) + opening_balance
- Only include POSTED journal entries (status = 'posted')
- Always scope by company_id

### 2. General Ledger Controller + View

**`Http/Controllers/GeneralLedger.php`:**
- `index()` — accepts query params: account_id (optional), start_date, end_date
- If account_id specified: show all journal lines for that account with running balance
- If no account_id: show all journal lines grouped by account
- Support CSV export via `?export=csv`

**`Resources/views/general-ledger/index.blade.php`:**
- Filters: Account dropdown (optional), Date From, Date To, Apply button, Export CSV button
- Table: Date, Reference, Description, Debit, Credit, Running Balance
- If showing all accounts: section headers per account with subtotals

### 3. Trial Balance Controller + View

**`Http/Controllers/TrialBalance.php`:**
- `index()` — accepts: as_of_date (defaults to today)

**`Resources/views/trial-balance/index.blade.php`:**
- Filter: As of Date, Apply button, Export CSV
- Table: Account Code, Account Name, Debit Balance, Credit Balance
- Grouped by type (Assets, Liabilities, Equity, Income, Expenses)
- Bottom row: **Total Debits** and **Total Credits** (must be equal)
- Visual indicator if they don't balance (red warning)

### 4. Balance Sheet Controller + View

**`Http/Controllers/BalanceSheet.php`:**
- `index()` — accepts: as_of_date (defaults to today)

**`Resources/views/balance-sheet/index.blade.php`:**
- Filter: As of Date, Apply button, Export CSV
- Three sections:
  - **Assets** — list accounts with balances, subtotal
  - **Liabilities** — list accounts with balances, subtotal
  - **Equity** — list accounts with balances, subtotal (includes retained earnings = net income)
- Bottom: **Total Assets** vs **Total Liabilities + Equity** (must be equal)
- Standard accounting format

### 5. Profit & Loss Controller + View

**`Http/Controllers/ProfitLoss.php`:**
- `index()` — accepts: start_date, end_date (defaults to current month)

**`Resources/views/profit-loss/index.blade.php`:**
- Filters: Date From, Date To, Apply button, Export CSV
- Two sections:
  - **Income** — list accounts with totals, subtotal
  - **Expenses** — list accounts with totals, subtotal
- Bottom: **Net Profit/Loss** = Total Income - Total Expenses
- Green if profit, red if loss

### 6. Account Defaults Controller + View

**`Http/Controllers/AccountDefaults.php`:**
- `index()` — show form with current defaults
- `store()` — save/update defaults

**`Resources/views/account-defaults/index.blade.php`:**
- Form with dropdowns for each default type:
  - Accounts Receivable (filter: asset accounts only)
  - Accounts Payable (filter: liability accounts only)
  - Sales Revenue (filter: income accounts only)
  - Cost of Goods Sold (filter: expense accounts only)
  - Bank/Checking (filter: asset accounts only)
  - Owner's Equity (filter: equity accounts only)
- Save button

### 7. CSV Export Helper
For each report, when `?export=csv` is passed:
- Set headers: `Content-Type: text/csv`, `Content-Disposition: attachment; filename="report-name-YYYY-MM-DD.csv"`
- Output report data as CSV
- Can be a shared trait or helper method

## Deploy & Verify
```bash
docker cp /home/valleybird/projects/akaunting-setup/modules/DoubleEntry akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/DoubleEntry
docker exec akaunting php artisan view:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan cache:clear

# Test all report pages
for page in general-ledger trial-balance balance-sheet profit-loss account-defaults; do
  echo -n "$page: "
  curl -s -o /dev/null -w "%{http_code}" "http://100.83.12.126:8085/1/double-entry/$page"
  echo ""
done
```

## Success Criteria
- [ ] General Ledger shows journal lines with running balances
- [ ] Trial Balance shows all accounts with debit/credit columns that balance
- [ ] Balance Sheet shows Assets = Liabilities + Equity
- [ ] P&L shows Income - Expenses = Net Profit
- [ ] All reports have date range filters
- [ ] CSV export works for each report
- [ ] Account Defaults page loads and saves correctly
- [ ] All pages use Akaunting Blade components

## Commit
`feat(double-entry): financial reports (GL, TB, BS, P&L) and account defaults`
