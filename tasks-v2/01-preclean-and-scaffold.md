# Task 01: Pre-Clean + Double-Entry Scaffold + Migrations + Seeder + Permissions

## Context
We're rebuilding the Double-Entry and Bank Feeds modules from scratch. The old modules are broken (wrong FK types, missing permissions, 403/404 errors). This task cleans the slate and builds a working scaffold.

## Pre-Clean Steps (RUN THESE FIRST)

### 1. Disable all custom modules
```bash
for mod in appointments auto-schedule-reports bank-feeds budgets credit-debit-notes crm custom-fields double-entry employees estimates expense-claims inventory paypal-sync payroll pos projects receipts roles sales-purchase-orders stripe; do
  docker exec akaunting php artisan module:disable "$mod" 2>/dev/null
done
```

### 2. Drop old tables
```bash
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "
DROP TABLE IF EXISTS nif_double_entry_journal_lines;
DROP TABLE IF EXISTS nif_double_entry_journals;
DROP TABLE IF EXISTS nif_double_entry_accounts;
DROP TABLE IF EXISTS nif_double_entry_account_defaults;
"
```

### 3. Remove old permissions
```bash
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "
DELETE FROM nif_role_permissions WHERE permission_id IN (
  SELECT id FROM nif_permissions WHERE name LIKE '%double-entry%'
);
DELETE FROM nif_permissions WHERE name LIKE '%double-entry%';
"
```

### 4. Remove old module from Docker
```bash
docker exec akaunting rm -rf /var/www/html/modules/DoubleEntry
```

### 5. Remove old migration records
```bash
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "
DELETE FROM nif_migrations WHERE migration LIKE '%double_entry%';
"
```

### 6. Delete old module source
```bash
rm -rf /home/valleybird/projects/akaunting-setup/modules/DoubleEntry
```

## What to Build

Build the complete module scaffold at `/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/`

### Directory Structure
```
modules/DoubleEntry/
├── Database/
│   ├── Migrations/
│   │   └── 2024_01_01_000001_create_double_entry_tables.php
│   └── Seeds/
│       └── DefaultAccounts.php
├── Http/
│   ├── Controllers/    (empty placeholder files for now)
│   └── Requests/       (empty placeholder files for now)
├── Listeners/
│   ├── AddAdminMenu.php
│   └── FinishInstallation.php
├── Models/
│   ├── Account.php
│   ├── AccountDefault.php
│   ├── Journal.php
│   └── JournalLine.php
├── Providers/
│   ├── Event.php
│   └── Main.php
├── Resources/
│   ├── lang/en-GB/general.php
│   └── views/ (empty directory for now)
├── Routes/
│   ├── admin.php
│   └── portal.php
├── Services/
│   └── AccountBalanceService.php (stub)
├── module.json
└── composer.json
```

### module.json
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

### Migration — CRITICAL TYPE RULES
- `company_id` → `$table->unsignedInteger('company_id')->index();` (NOT bigInteger!)
- `parent_id` → `$table->unsignedInteger('parent_id')->nullable()->index();`
- All FKs to core Akaunting tables: NO FK constraints. Just column + index.
- FKs between module tables: OK to use constraints.
- All money columns: `decimal(15,4)`

#### Tables:
1. `nif_double_entry_accounts` — id (unsignedInteger AI PK), company_id, parent_id (nullable, self-ref FK), code (varchar 50), name (varchar 191), type (enum: asset/liability/equity/income/expense), description (text nullable), opening_balance (decimal 15,4 default 0), enabled (boolean default true), timestamps, softDeletes
2. `nif_double_entry_journals` — id, company_id, date, reference (varchar 100 nullable), description (text nullable), basis (enum: accrual/cash default accrual), status (enum: draft/posted default draft), documentable_type (varchar 255 nullable), documentable_id (unsignedInteger nullable), recurring_frequency (enum: weekly/monthly/quarterly/yearly nullable), next_recurring_date (date nullable), created_by (unsignedInteger nullable), timestamps, softDeletes
3. `nif_double_entry_journal_lines` — id, journal_id (unsignedInteger, FK to journals), account_id (unsignedInteger, INDEX, NO FK), debit (decimal 15,4 default 0), credit (decimal 15,4 default 0), description (text nullable)
4. `nif_double_entry_account_defaults` — id, company_id, type (varchar 50), account_id (unsignedInteger, INDEX), timestamps

### Default COA Seeder
Seed these accounts for company_id = 1:

**Assets (1xxx):** 1000 Cash, 1010 Checking Account, 1020 Savings Account, 1100 Accounts Receivable, 1200 Inventory, 1300 Prepaid Expenses, 1500 Equipment, 1510 Accumulated Depreciation, 1600 Vehicles, 1610 Accumulated Depreciation - Vehicles

**Liabilities (2xxx):** 2000 Accounts Payable, 2100 Credit Cards, 2200 Payroll Liabilities, 2300 Sales Tax Payable, 2400 Short-Term Loans, 2500 Long-Term Loans, 2600 Equipment Loans

**Equity (3xxx):** 3000 Owner's Equity, 3100 Owner's Draw, 3200 Retained Earnings

**Income (4xxx):** 4000 Construction Revenue, 4010 Owner Representative Fees, 4020 Reimbursement Income, 4100 Material Markup, 4200 Service Revenue, 4900 Other Income

**Expenses (5xxx):** 5000 COGS - Materials, 5010 COGS - Labor, 5020 Subcontractor Costs, 5100 Advertising & Marketing, 5200 Auto & Truck, 5210 Fuel, 5300 Insurance, 5310 Workers Comp Insurance, 5400 Office Supplies, 5500 Professional Fees, 5600 Rent/Lease, 5700 Repairs & Maintenance, 5800 Tools & Small Equipment, 5900 Utilities, 5950 Permits & Licenses, 5960 Bonding, 5999 Miscellaneous Expense

### Providers — copy OfflinePayments pattern exactly

**Main.php:**
```php
namespace Modules\DoubleEntry\Providers;
use App\Abstracts\Providers\Main as Provider;

class Main extends Provider {
    public function boot() {
        $this->loadTranslations();
        $this->loadViews();
    }
    public function register() {
        $this->loadRoutes();
    }
}
```

**Event.php:**
```php
namespace Modules\DoubleEntry\Providers;
use App\Abstracts\Providers\Event as Provider;

class Event extends Provider {
    public function shouldDiscoverEvents() { return true; }
    protected function discoverEventsWithin() {
        return [__DIR__ . '/../Listeners'];
    }
}
```

### FinishInstallation.php
```php
use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation {
    use Permissions;
    public $alias = 'double-entry';

    public function handle(Event $event) {
        if ($event->alias != $this->alias) return;
        $this->updatePermissions();
    }

    protected function updatePermissions() {
        $this->attachPermissionsToAdminRoles([
            'double-entry-accounts' => 'c,r,u,d',
            'double-entry-journals' => 'c,r,u,d',
            'double-entry-account-defaults' => 'r,u',
            'double-entry-general-ledger' => 'r',
            'double-entry-trial-balance' => 'r',
            'double-entry-balance-sheet' => 'r',
            'double-entry-profit-loss' => 'r',
        ]);
    }
}
```

### AddAdminMenu.php
Single listener. Top-level "Double-Entry" with children: Chart of Accounts, Journal Entries, General Ledger, Trial Balance, Balance Sheet, Profit & Loss, Account Defaults.

### Routes/admin.php
```php
use Illuminate\Support\Facades\Route;

Route::admin('double-entry', function () {
    Route::resource('accounts', 'Accounts');
    Route::get('accounts/import', 'Accounts@import')->name('accounts.import');
    Route::post('accounts/import', 'Accounts@importProcess');
    Route::resource('journals', 'Journals');
    Route::get('general-ledger', 'GeneralLedger@index')->name('general-ledger.index');
    Route::get('trial-balance', 'TrialBalance@index')->name('trial-balance.index');
    Route::get('balance-sheet', 'BalanceSheet@index')->name('balance-sheet.index');
    Route::get('profit-loss', 'ProfitLoss@index')->name('profit-loss.index');
    Route::get('account-defaults', 'AccountDefaults@index')->name('account-defaults.index');
    Route::post('account-defaults', 'AccountDefaults@store')->name('account-defaults.store');
});
```

### Models
- All models scoped by company_id
- Account: fillable fields, type casting, parent/children relationships, journalLines relationship
- Journal: fillable fields, lines relationship, balanced() check
- JournalLine: belongs to journal and account
- AccountDefault: belongs to account

### Controllers (stubs for now)
Create placeholder controllers that extend `App\Abstracts\Http\Controller` with index methods that return basic views. Full CRUD comes in Task 02.

### Views (minimal)
Create a basic `accounts/index.blade.php` that extends Akaunting's layout and shows "Chart of Accounts — Coming Soon". This validates the sidebar link works and permissions are correct.

## Deploy & Verify

```bash
# Copy to Docker
docker cp /home/valleybird/projects/akaunting-setup/modules/DoubleEntry akaunting:/var/www/html/modules/

# Set permissions
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/DoubleEntry

# Run migrations
docker exec akaunting php artisan migrate --force

# Enable module
docker exec akaunting php artisan module:enable double-entry

# Seed default accounts
docker exec akaunting php artisan tinker --execute="
\$seeder = new \Modules\DoubleEntry\Database\Seeds\DefaultAccounts();
\$seeder->run();
"

# Trigger permission installation
docker exec akaunting php artisan tinker --execute="
event(new \App\Events\Module\Installed('double-entry', '1'));
"

# Clear caches
docker exec akaunting php artisan config:clear
docker exec akaunting php artisan cache:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan view:clear

# Verify permissions were created
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "SELECT name FROM nif_permissions WHERE name LIKE '%double-entry%' ORDER BY name;"

# Verify accounts were seeded
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "SELECT code, name, type FROM nif_double_entry_accounts ORDER BY code;"

# Verify routes registered
docker exec akaunting php artisan route:list --name=double-entry

# Test page load (should be 302 redirect to login, not 404)
docker exec akaunting curl -s -o /dev/null -w "%{http_code}" http://localhost/1/double-entry/accounts
```

## Success Criteria
- [ ] Module enables without errors
- [ ] 4 tables created with correct column types (unsignedInteger for company_id!)
- [ ] 40+ default accounts seeded
- [ ] Permissions created and assigned to admin role
- [ ] Sidebar shows "Double-Entry" with all child menu items
- [ ] `/1/double-entry/accounts` returns 302 (not 404 or 403)
- [ ] Routes registered for all endpoints

## Commit
`feat(double-entry): scaffold, migrations, default COA seed, permissions`
