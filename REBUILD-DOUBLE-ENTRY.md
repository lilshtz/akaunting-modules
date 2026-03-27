# REBUILD: Double-Entry Module — Proper Architecture

## Context
We built 20 Akaunting modules but the implementation has issues: duplicate sidebar entries, 403 permissions errors, no permission registration, slow loading. We're starting over with Double-Entry as the first properly-built module.

## Akaunting Module Architecture (from docs + source analysis)

### Route::admin() macro
```php
Route::admin('double-entry', function () {
    Route::resource('accounts', 'Accounts');
});
```
This auto-applies:
- `admin` middleware (handles auth + company scoping)
- `{company_id}/double-entry/` prefix
- `double-entry.` route name prefix
- `Modules\DoubleEntry\Http\Controllers` namespace

### Provider Pattern (copy OfflinePayments exactly)
**Main.php** — loads routes, views, translations:
```php
class Main extends Provider {
    public function boot() { $this->loadTranslations(); $this->loadViews(); }
    public function register() { $this->loadRoutes(); }
}
```

**Event.php** — auto-discovers listeners:
```php
class Event extends Provider {
    public function shouldDiscoverEvents() { return true; }
    protected function discoverEventsWithin() { return [__DIR__ . '/../Listeners']; }
}
```

### Permission Registration (CRITICAL — this was missing)
**Listeners/FinishInstallation.php** — runs on module install:
```php
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
            'double-entry-reports' => 'r',
        ]);
    }
}
```

### Sidebar Menu (single registration)
**Listeners/AddAdminMenu.php** — listen to `AdminCreated`:
```php
use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu {
    public function handle(Event $event): void {
        $event->menu->add([
            'route' => ['double-entry.accounts.index', []],
            'title' => 'Double-Entry',
            'icon' => 'balance',
            'order' => 15,
        ]);
        
        // Add children under the new menu item
        $item = $event->menu->whereTitle('Double-Entry');
        $item->route('double-entry.accounts.index', 'Chart of Accounts', [], 1, ['icon' => '']);
        $item->route('double-entry.journals.index', 'Journal Entries', [], 2, ['icon' => '']);
        // etc.
    }
}
```
NOTE: Only ONE listener for the menu. Event provider auto-discovers it.

### Controller Pattern
Extend `App\Abstracts\Http\Controller` which auto-assigns permissions via:
```php
public function __construct() {
    $this->assignPermissionsToController();
}
```
This matches route names to permission names automatically.

### Model Pattern  
- Use `unsignedInteger('company_id')` NOT `unsignedBigInteger` (Akaunting core uses int)
- Table names WITHOUT `nif_` prefix (the DB prefix handles it automatically)
- Use `company_id` scoping via Akaunting's company scope trait
- Soft deletes where appropriate

### Migration Pattern
- Use `return new class extends Migration { ... };` (anonymous class)
- Table names are bare (e.g., `double_entry_accounts` not `nif_double_entry_accounts`)
- Foreign keys to core tables: use `unsignedInteger` and NO FK constraint (just index)
- Foreign keys between module tables: OK to use FK constraints

### View Pattern
- Use Akaunting's existing Blade components: `<x-form>`, `<x-form.group.text>`, `<x-table>`, etc.
- Reference `modules/_reference_views/` for component usage
- Views are namespaced: `double-entry::accounts.index`

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

## What to Build

### 1. Chart of Accounts
- CRUD: list (grouped by type with hierarchy), create, edit, delete
- Account types: Asset, Liability, Equity, Income, Expense
- Sub-accounts (parent/child, unlimited depth)
- Account codes (1000-5999)
- Opening balances
- Enable/disable
- CSV import (QuickBooks compatible)
- Default COA seed

### 2. Journal Entries
- Manual entries with debit/credit lines (must balance)
- Auto-posting from invoices/bills/payments/transfers (via event listeners)
- Recurring journals
- Draft vs Posted status

### 3. Reports
- General Ledger (journal lines per account with running balances)
- Trial Balance (debit/credit totals per account)
- Balance Sheet (assets = liabilities + equity)
- Profit & Loss (income - expenses = net profit)
- All with date range filters, export to CSV/PDF

### 4. Account Defaults
- Map system types to COA accounts (AR, AP, Sales, Bank, etc.)
- Used by auto-posting listeners to know which accounts to debit/credit

## Files to Create

```
modules/DoubleEntry/
├── Database/
│   ├── Migrations/
│   │   └── 2024_01_01_000001_create_double_entry_tables.php
│   └── Seeds/
│       └── DefaultAccounts.php
├── Http/
│   ├── Controllers/
│   │   ├── Accounts.php
│   │   ├── AccountDefaults.php
│   │   ├── Journals.php
│   │   ├── GeneralLedger.php
│   │   ├── TrialBalance.php
│   │   ├── BalanceSheet.php
│   │   └── ProfitLoss.php
│   └── Requests/
│       ├── AccountStore.php
│       ├── AccountUpdate.php
│       ├── JournalStore.php
│       └── JournalUpdate.php
├── Listeners/
│   ├── AddAdminMenu.php
│   ├── FinishInstallation.php
│   ├── DocumentCreated.php
│   ├── DocumentUpdated.php
│   ├── TransactionCreated.php
│   └── TransferCreated.php
├── Models/
│   ├── Account.php
│   ├── AccountDefault.php
│   ├── Journal.php
│   └── JournalLine.php
├── Providers/
│   ├── Event.php
│   └── Main.php
├── Resources/
│   ├── lang/
│   │   └── en-GB/
│   │       └── general.php
│   └── views/
│       ├── accounts/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── import.blade.php
│       ├── journals/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── show.blade.php
│       │   └── edit.blade.php
│       ├── general-ledger/
│       │   └── index.blade.php
│       ├── trial-balance/
│       │   └── index.blade.php
│       ├── balance-sheet/
│       │   └── index.blade.php
│       └── profit-loss/
│           └── index.blade.php
├── Routes/
│   ├── admin.php
│   └── portal.php
├── Services/
│   └── AccountBalanceService.php
├── module.json
└── composer.json
```

## CRITICAL REQUIREMENTS

1. **Permissions**: FinishInstallation listener MUST register permissions or admin gets 403
2. **Single sidebar entry**: ONE AddAdminMenu listener, no duplicates
3. **company_id as unsignedInteger**: Match Akaunting core's int(10) unsigned, NOT bigint
4. **No FK constraints to core tables**: Just index + unsignedInteger column
5. **Route::admin() macro**: Use it exactly like OfflinePayments does
6. **Blade components**: Use Akaunting's x-components, not raw HTML forms
7. **Controller extends App\Abstracts\Http\Controller**: For auto-permission assignment
8. **All queries scoped by company_id**: Multi-tenant isolation
9. **Decimal(15,4)** for all financial amounts

## After Building

1. Delete the old broken DoubleEntry module: `rm -rf modules/DoubleEntry`
2. Place the new one
3. Copy to Docker: `docker cp modules/DoubleEntry akaunting:/var/www/html/modules/`
4. Set permissions: `docker exec akaunting chown -R www-data:www-data /var/www/html/modules/DoubleEntry`
5. Run migrations: `docker exec akaunting php artisan migrate --force`
6. Install: `docker exec akaunting php artisan module:install double-entry 1`
7. Clear caches: `docker exec akaunting php artisan config:clear && cache:clear && route:clear && view:clear`
8. Test: navigate to the Chart of Accounts page, verify no 403, no duplicate sidebar, page loads fast

## Reference Files (in this repo)
- `modules/_reference_OfflinePayments/` — canonical module structure
- `modules/_reference_models/` — Akaunting core models (see how they scope by company)
- `modules/_reference_views/` — Akaunting Blade components
