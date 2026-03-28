# Task 02: Double-Entry — Chart of Accounts CRUD

## Context
The Double-Entry module scaffold is deployed and working (Task 01 verified — 302 on accounts page). Tables exist, default COA is seeded, permissions are registered. Now build the full CRUD for Chart of Accounts.

## Environment
- Akaunting Docker container: `akaunting` on port 8085
- DB: `akaunting-db`, user `akaunting`, password `akaunting_dev`, database `akaunting`, prefix `nif_`
- Module source: `/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/`
- Admin user: andrew.r.malik@gmail.com (company_id 1)
- APP_URL: http://100.83.12.126:8085
- To test HTTP: use `curl -s http://100.83.12.126:8085/...` (NOT localhost — Host header matters)

## What to Build

All files go in `/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/`

### 1. Accounts Controller (`Http/Controllers/Accounts.php`)
Extend `App\Abstracts\Http\Controller`. Implement:
- `index()` — List all accounts grouped by type (Asset, Liability, Equity, Income, Expense), with parent/child hierarchy shown via indentation
- `create()` — Form to create new account
- `store()` — Validate and save new account
- `edit($id)` — Form to edit existing account
- `update($id)` — Validate and update account
- `destroy($id)` — Soft delete (only if no journal lines reference this account)
- `import()` — Show CSV import form
- `importProcess()` — Parse and import CSV (QuickBooks format: Account, Type, Description, Balance)

All queries MUST be scoped by `company_id = company_id()`.

### 2. Form Requests
**`Http/Requests/AccountStore.php`:**
- code: required, string, max:50, unique within company
- name: required, string, max:191
- type: required, in:asset,liability,equity,income,expense
- parent_id: nullable, exists in double_entry_accounts
- description: nullable, string
- opening_balance: nullable, numeric
- enabled: boolean

**`Http/Requests/AccountUpdate.php`:**
Same as Store but code uniqueness excludes current record.

### 3. Account Model (`Models/Account.php`)
- Table: `double_entry_accounts` (prefix handled by config)
- Fillable: company_id, parent_id, code, name, type, description, opening_balance, enabled
- Casts: opening_balance → decimal:4, enabled → boolean
- Relationships: parent(), children(), journalLines()
- Scope: `scopeByCompany($query)` using `company_id()`
- Accessor: `getBalanceAttribute()` — calculates current balance from journal lines

### 4. Views (use Akaunting Blade components)

**`Resources/views/accounts/index.blade.php`:**
- Extends `layouts.admin`
- Page title: "Chart of Accounts"
- "Add New" button linking to create
- "Import" button linking to import
- Table grouped by account type (section headers: Assets, Liabilities, Equity, Income, Expenses)
- Within each group, show hierarchy (indent child accounts with padding/prefix)
- Columns: Code, Name, Type, Balance, Status (enabled/disabled), Actions (edit/delete)
- Use `<x-table>`, `<x-table.thead>`, `<x-table.tbody>`, `<x-table.tr>`, `<x-table.td>`

**`Resources/views/accounts/create.blade.php`:**
- Form with fields: Code, Name, Type (dropdown), Parent Account (dropdown, filtered by type), Description, Opening Balance, Enabled (checkbox)
- Use `<x-form>`, `<x-form.group.text>`, `<x-form.group.select>`, `<x-form.group.textarea>`
- Save and Cancel buttons

**`Resources/views/accounts/edit.blade.php`:**
- Same form as create, pre-populated with existing data

**`Resources/views/accounts/import.blade.php`:**
- File upload for CSV
- Instructions: "Upload a CSV with columns: Account (name), Type, Description, Balance"
- Submit button

### 5. Language File (`Resources/lang/en-GB/general.php`)
Return array with translations:
- 'name' => 'Double-Entry'
- 'accounts' => 'Chart of Accounts'
- 'journals' => 'Journal Entries'
- 'general_ledger' => 'General Ledger'
- 'trial_balance' => 'Trial Balance'
- 'balance_sheet' => 'Balance Sheet'
- 'profit_loss' => 'Profit & Loss'
- 'account_defaults' => 'Account Defaults'
- 'debit' => 'Debit'
- 'credit' => 'Credit'
- Plus any other strings needed

### 6. Enable/Disable Toggle
Add a route for toggling enabled status:
```php
Route::patch('accounts/{account}/toggle', 'Accounts@toggle')->name('accounts.toggle');
```

## Important Notes
- Check how existing Akaunting controllers (e.g., `App\Http\Controllers\Banking\Accounts`) structure their index/create/store methods and follow the same patterns
- Use `company_id()` helper (NOT `auth()->user()->company_id`) for tenant scoping
- The Account model should NOT have FK constraints to core tables in the migration (already done in Task 01)
- Blade views should use `@section('title')` and `@section('content')` matching Akaunting's admin layout

## Deploy & Verify
```bash
# Copy to Docker
docker cp /home/valleybird/projects/akaunting-setup/modules/DoubleEntry akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/DoubleEntry

# Clear caches
docker exec akaunting php artisan view:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan cache:clear

# Verify routes
docker exec akaunting php artisan route:list --name=double-entry.accounts

# Test page loads (should be 302 redirect to login)
curl -s -o /dev/null -w "%{http_code}" http://100.83.12.126:8085/1/double-entry/accounts
curl -s -o /dev/null -w "%{http_code}" http://100.83.12.126:8085/1/double-entry/accounts/create
```

## Success Criteria
- [ ] Accounts list page loads and shows seeded COA grouped by type
- [ ] Can create a new account with all fields
- [ ] Can edit an existing account
- [ ] Can delete an account (only if no journal lines)
- [ ] Parent/child hierarchy displays with indentation
- [ ] CSV import parses and creates accounts
- [ ] Enable/disable toggle works
- [ ] All pages use Akaunting Blade components (consistent look)

## Commit
`feat(double-entry): chart of accounts CRUD with import and hierarchy`
