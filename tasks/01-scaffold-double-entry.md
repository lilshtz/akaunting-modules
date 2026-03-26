# Task 01: Scaffold Double-Entry Module + Chart of Accounts

## Context
We're building custom Laravel modules for Akaunting (v3.x, Laravel 10, MariaDB) that replicate paid features. This is the FIRST and most critical module — Double-Entry Accounting. Everything else depends on it.

Akaunting runs in a Docker container. Modules live in `/var/www/html/modules/`. Each module is a Laravel package with Controllers, Models, Migrations, Views, Routes, Providers, and a `module.json`.

Reference the existing `OfflinePayments` module at `/var/www/html/modules/OfflinePayments/` for structure.

## Objective
Create the DoubleEntry module scaffold and implement the Chart of Accounts (COA) feature.

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/DoubleEntry/` with:
```
DoubleEntry/
├── Console/
├── Database/
│   ├── Migrations/
│   └── Seeds/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Jobs/
├── Listeners/
├── Models/
├── Providers/
│   ├── Event.php
│   └── Main.php
├── Resources/
│   ├── assets/
│   ├── lang/
│   │   └── en-GB/
│   └── views/
├── Routes/
│   ├── admin.php
│   └── portal.php
├── module.json
└── composer.json
```

### 2. module.json
```json
{
    "alias": "double-entry",
    "icon": "balance",
    "version": "1.0.0",
    "active": 1,
    "providers": [
        "Modules\\DoubleEntry\\Providers\\Event",
        "Modules\\DoubleEntry\\Providers\\Main"
    ],
    "aliases": {},
    "files": [],
    "requires": [],
    "reports": [],
    "widgets": [],
    "settings": [],
    "extra-modules": {},
    "routes": {}
}
```

### 3. Database Migration: Chart of Accounts
Create table `double_entry_accounts`:
- `id` — bigint unsigned, auto-increment, primary key
- `company_id` — bigint unsigned, foreign key to `companies.id`
- `parent_id` — bigint unsigned nullable, self-referential FK (for sub-accounts)
- `code` — varchar(50), account code (e.g., "1000", "1200")
- `name` — varchar(255)
- `type` — enum: 'asset', 'liability', 'equity', 'income', 'expense'
- `description` — text nullable
- `opening_balance` — decimal(15,4) default 0
- `enabled` — boolean default true
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

Index: `company_id`, `type`, `code` (unique per company)

Create table `double_entry_account_defaults`:
- `id` — bigint unsigned PK
- `company_id` — bigint unsigned FK
- `type` — varchar(50) (e.g., "accounts_receivable", "accounts_payable", "sales", "expenses", "bank")
- `account_id` — bigint unsigned FK to double_entry_accounts

### 4. Model: Account
- Eloquent model at `Models/Account.php`
- Belongs to Company
- Has many children (self-referential: parent_id)
- Belongs to parent (nullable)
- Scoped by company_id
- Soft deletes

### 5. Controller: Accounts
CRUD controller at `Http/Controllers/Accounts.php`:
- `index()` — List accounts, grouped by type, showing hierarchy
- `create()` — Form to create account (fields: code, name, type, parent, description, opening_balance, enabled)
- `store()` — Validate and save
- `edit($id)` — Edit form
- `update($id)` — Validate and update
- `destroy($id)` — Soft delete (only if no journal entries reference it)
- `import()` — CSV import page
- `importProcess()` — Handle CSV upload (columns: code, name, type, parent_code, description, opening_balance)

### 6. Views (Blade templates)
Use Akaunting's existing component system. Look at how core views work in `/var/www/html/resources/views/` for the pattern.
- `views/accounts/index.blade.php` — Table grouped by account type with tree structure for sub-accounts
- `views/accounts/create.blade.php` — Create form
- `views/accounts/edit.blade.php` — Edit form
- `views/accounts/import.blade.php` — CSV import form

### 7. Routes (admin.php)
```php
Route::group(['prefix' => 'double-entry', 'namespace' => 'Modules\DoubleEntry\Http\Controllers'], function () {
    Route::resource('accounts', 'Accounts');
    Route::get('accounts/import', 'Accounts@import')->name('double-entry.accounts.import');
    Route::post('accounts/import', 'Accounts@importProcess')->name('double-entry.accounts.import.process');
});
```

### 8. Sidebar Menu
Register in Event.php listener to add "Double-Entry" section to left sidebar with sub-items:
- Chart of Accounts
- Journal Entries (link only — built in task 02)
- General Ledger (link only — built in task 03)
- Trial Balance (link only — built in task 03)
- Balance Sheet (link only — built in task 04)
- Profit & Loss (link only — built in task 04)

### 9. Seed Data
Default COA seed for new company installations:
```
1000 Cash (Asset)
1100 Accounts Receivable (Asset)
1200 Inventory (Asset)
1300 Prepaid Expenses (Asset)
1500 Fixed Assets (Asset)
1510 Accumulated Depreciation (Asset)
2000 Accounts Payable (Liability)
2100 Accrued Liabilities (Liability)
2200 Notes Payable (Liability)
2300 Sales Tax Payable (Liability)
3000 Owner's Equity (Equity)
3100 Retained Earnings (Equity)
3200 Owner's Draw (Equity)
4000 Sales Revenue (Income)
4100 Service Revenue (Income)
4200 Other Income (Income)
5000 Cost of Goods Sold (Expense)
5100 Materials (Expense)
5200 Labor (Expense)
5300 Subcontractor Costs (Expense)
6000 Rent Expense (Expense)
6100 Utilities (Expense)
6200 Insurance (Expense)
6300 Office Supplies (Expense)
6400 Vehicle Expense (Expense)
6500 Tools & Equipment (Expense)
6600 Professional Fees (Expense)
6700 Advertising (Expense)
6800 Depreciation (Expense)
6900 Miscellaneous Expense (Expense)
```

## Files to Create/Modify
- `/var/www/html/modules/DoubleEntry/` (entire module directory)
- All files listed in scaffold structure above

## Verification
1. `php artisan module:list` shows DoubleEntry as Enabled
2. Navigate to `/double-entry/accounts` — see COA list
3. Create a new account — saves to DB, shows in list
4. Create a sub-account with parent — shows indented in hierarchy
5. Import a CSV — accounts created correctly
6. Sidebar shows "Double-Entry" section with links

## Commit Message
`feat(modules): scaffold double-entry module with chart of accounts`
