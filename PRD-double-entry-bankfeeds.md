# PRD: Akaunting Double-Entry + Bank Feeds — Clean Rebuild

## Problem

We built 20 Akaunting modules in one pass. They all "installed" but are broken in practice:
- **403 Permission errors** — FinishInstallation listeners never actually ran, so no permissions were inserted into the DB. Admin role can't access any module pages.
- **404 on page load** — Routes register but controllers fail due to missing permissions, incorrect Blade component usage, or broken model scoping.
- **`bigint` vs `int` FK mismatch** — Module tables use `bigint(20)` for `company_id`, but Akaunting core uses `int(10) unsigned`. FK constraints may silently fail or cause join issues.
- **Tables exist but are empty and untested** — Schema was migrated but never validated with actual data flow.
- **No default Chart of Accounts seeded** — Double-Entry is useless without a default COA.

## Goals

1. **Rebuild Double-Entry module from scratch** — working Chart of Accounts, Journal Entries, General Ledger, Trial Balance, Balance Sheet, P&L reports. Fully functional, tested in browser.
2. **Rebuild Bank Feeds module from scratch** — CSV/OFX import, column mapping, categorization rules, transaction matching, reconciliation. Depends on Double-Entry for account mapping.
3. **Both modules must be individually verified in-browser** before marking complete.
4. **Do NOT touch any other modules.** They will be disabled in Docker before this build starts.

## Environment

- **Akaunting:** Docker container `akaunting` on port 8085, Laravel 10.50.0
- **Database:** MariaDB 11.x in `akaunting-db` container
  - User: `akaunting` / Password: `akaunting_dev` / DB: `akaunting`
  - Table prefix: `nif_`
- **Admin user:** andrew.r.malik@gmail.com (role: admin, company_id: 1)
- **Module source:** `/home/valleybird/projects/akaunting-setup/modules/`
- **Deploy to Docker:** `docker cp modules/MODULE akaunting:/var/www/html/modules/`
- **Reference module:** `OfflinePayments` in Docker at `/var/www/html/modules/OfflinePayments/`
- **GitHub:** https://github.com/lilshtz/akaunting-modules

## Pre-Build: Clean Slate

Before building, the following must happen:

### 1. Disable all custom modules (keep OfflinePayments + PaypalStandard)
```bash
for mod in appointments auto-schedule-reports bank-feeds budgets credit-debit-notes crm custom-fields double-entry employees estimates expense-claims inventory paypal-sync payroll pos projects receipts roles sales-purchase-orders stripe; do
  docker exec akaunting php artisan module:disable "$mod" 2>/dev/null
done
```

### 2. Drop existing broken tables for Double-Entry and Bank Feeds
```sql
DROP TABLE IF EXISTS nif_double_entry_journal_lines;
DROP TABLE IF EXISTS nif_double_entry_journals;
DROP TABLE IF EXISTS nif_double_entry_accounts;
DROP TABLE IF EXISTS nif_bank_feed_transactions;
DROP TABLE IF EXISTS nif_bank_feed_rules;
DROP TABLE IF EXISTS nif_bank_feed_reconciliations;
DROP TABLE IF EXISTS nif_bank_feed_imports;
```

### 3. Remove old permissions
```sql
DELETE FROM nif_role_permissions WHERE permission_id IN (
  SELECT id FROM nif_permissions WHERE name LIKE '%double-entry%' OR name LIKE '%bank-feed%'
);
DELETE FROM nif_permissions WHERE name LIKE '%double-entry%' OR name LIKE '%bank-feed%';
```

### 4. Remove old module files from Docker
```bash
docker exec akaunting rm -rf /var/www/html/modules/DoubleEntry
docker exec akaunting rm -rf /var/www/html/modules/BankFeeds
```

### 5. Remove old migration records
```sql
DELETE FROM nif_migrations WHERE migration LIKE '%double_entry%' OR migration LIKE '%bank_feed%';
```

## Architecture Rules (CRITICAL — follow exactly)

These rules come from analyzing the working OfflinePayments module and Akaunting core. Every prior build failed because these were violated.

### 1. column types must match Akaunting core
- `company_id` → `unsignedInteger` (NOT `unsignedBigInteger`). Core `nif_companies.id` is `int(10) unsigned`.
- All FK references to core tables (categories, contacts, accounts, documents, transactions) → `unsignedInteger` + index. **NO foreign key constraints to core tables.** Just the column + index.
- FK constraints between module tables are OK.

### 2. Providers: exactly two, follow OfflinePayments pattern
**Providers/Main.php:**
```php
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

**Providers/Event.php:**
```php
class Event extends Provider {
    public function shouldDiscoverEvents() { return true; }
    protected function discoverEventsWithin() {
        return [__DIR__ . '/../Listeners'];
    }
}
```

### 3. Routes: use Route::admin() macro
```php
Route::admin('double-entry', function () {
    Route::resource('accounts', 'Accounts');
    // etc.
});
```
This auto-applies: `admin` middleware, `{company_id}/double-entry/` prefix, `double-entry.` route name prefix, correct namespace.

### 4. Controllers: extend App\Abstracts\Http\Controller
```php
use App\Abstracts\Http\Controller;
class Accounts extends Controller {
    // auto-permission assignment via constructor
}
```

### 5. Permissions: FinishInstallation listener + manual trigger
The listener handles fresh installs. But since we're deploying to an existing install, we ALSO need to manually trigger permission insertion after deploy:
```bash
docker exec akaunting php artisan tinker --execute="
event(new \App\Events\Module\Installed('double-entry', '1'));
"
```

### 6. Permission naming: `{action}-{module-alias}-{resource}`
Example: `create-double-entry-accounts`, `read-double-entry-journals`
The `attachPermissionsToAdminRoles` helper auto-expands `'double-entry-accounts' => 'c,r,u,d'` into:
- `create-double-entry-accounts`
- `read-double-entry-accounts`
- `update-double-entry-accounts`
- `delete-double-entry-accounts`

### 7. Sidebar: ONE AddAdminMenu listener, auto-discovered
```php
use App\Events\Menu\AdminCreated as Event;
class AddAdminMenu {
    public function handle(Event $event): void {
        // Single top-level menu item with children
    }
}
```

### 8. Views: use Akaunting Blade components
`<x-form>`, `<x-form.group.text>`, `<x-table>`, `<x-table.thead>`, `<x-table.tbody>`, `<x-table.tr>`, `<x-table.td>`, etc.

### 9. Models: company_id scoping
All models must use Akaunting's company scope trait or manually scope by `company_id` in queries.

### 10. module.json format
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

---

## Module 1: Double-Entry

### Database Tables

#### `nif_double_entry_accounts`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | NO FK constraint |
| parent_id | unsignedInteger, nullable, INDEX | Self-referencing FK OK |
| code | varchar(50) | e.g., "1000", "2100" |
| name | varchar(191) | |
| type | enum: asset, liability, equity, income, expense | |
| description | text, nullable | |
| opening_balance | decimal(15,4), default 0 | |
| enabled | boolean, default true | |
| created_at, updated_at | timestamps | |
| deleted_at | softDeletes | |

#### `nif_double_entry_journals`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| date | date | |
| reference | varchar(100), nullable | Auto-generated: JE-0001 |
| description | text, nullable | |
| basis | enum: accrual, cash. Default accrual | |
| status | enum: draft, posted. Default draft | |
| documentable_type | varchar(255), nullable | For linking to invoices/bills |
| documentable_id | unsignedInteger, nullable | |
| recurring_frequency | enum: weekly, monthly, quarterly, yearly, nullable | |
| next_recurring_date | date, nullable | |
| created_by | unsignedInteger, nullable | |
| created_at, updated_at | timestamps | |
| deleted_at | softDeletes | |

#### `nif_double_entry_journal_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| journal_id | unsignedInteger, FK to journals | |
| account_id | unsignedInteger, INDEX | NO FK to accounts (cross-module safety) |
| debit | decimal(15,4), default 0 | |
| credit | decimal(15,4), default 0 | |
| description | text, nullable | |

#### `nif_double_entry_account_defaults`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| type | varchar(50) | e.g., accounts_receivable, accounts_payable, sales, bank |
| account_id | unsignedInteger, INDEX | |
| created_at, updated_at | timestamps | |

### Default Chart of Accounts (Seeder)

Seed on install with standard COA for a construction/contracting business:

**Assets (1000s):**
- 1000 Cash
- 1010 Checking Account
- 1020 Savings Account
- 1100 Accounts Receivable
- 1200 Inventory
- 1300 Prepaid Expenses
- 1500 Equipment
- 1510 Accumulated Depreciation (contra)
- 1600 Vehicles
- 1610 Accumulated Depreciation - Vehicles (contra)

**Liabilities (2000s):**
- 2000 Accounts Payable
- 2100 Credit Cards
- 2200 Payroll Liabilities
- 2300 Sales Tax Payable
- 2400 Short-Term Loans
- 2500 Long-Term Loans
- 2600 Equipment Loans

**Equity (3000s):**
- 3000 Owner's Equity
- 3100 Owner's Draw
- 3200 Retained Earnings

**Income (4000s):**
- 4000 Construction Revenue
- 4010 Owner Representative Fees
- 4020 Reimbursement Income
- 4100 Material Markup
- 4200 Service Revenue
- 4900 Other Income

**Expenses (5000s):**
- 5000 Cost of Goods Sold - Materials
- 5010 Cost of Goods Sold - Labor
- 5020 Subcontractor Costs
- 5100 Advertising & Marketing
- 5200 Auto & Truck
- 5210 Fuel
- 5300 Insurance
- 5310 Workers Comp Insurance
- 5400 Office Supplies
- 5500 Professional Fees (Legal/Accounting)
- 5600 Rent/Lease
- 5700 Repairs & Maintenance
- 5800 Tools & Small Equipment
- 5900 Utilities
- 5950 Permits & Licenses
- 5960 Bonding
- 5999 Miscellaneous Expense

### Features

1. **Chart of Accounts CRUD**
   - List page grouped by type (Asset → Liability → Equity → Income → Expense)
   - Hierarchical display (parent/child with indentation)
   - Create/Edit with: code, name, type, parent account, description, opening balance, enabled
   - Delete (soft delete, only if no journal lines reference it)
   - CSV Import (QuickBooks-compatible: Account, Type, Description, Balance)
   - Enable/disable toggle

2. **Journal Entries CRUD**
   - List page: date, reference, description, total amount, status (draft/posted)
   - Create: date, reference (auto-gen), description, debit/credit lines
   - **Validation: total debits MUST equal total credits.** Reject if unbalanced.
   - At least 2 lines per entry
   - Post/unpost toggle
   - Edit (only drafts)
   - Delete (only drafts)

3. **Account Defaults**
   - Settings page to map system types to COA accounts
   - Types: accounts_receivable, accounts_payable, sales, cost_of_goods, bank, owner_equity
   - Used by auto-posting (future: when invoices/bills are created)

4. **Reports**
   - **General Ledger:** All journal lines for a selected account or all accounts, with running balance. Date range filter.
   - **Trial Balance:** List all accounts with total debit, total credit columns. Must balance.
   - **Balance Sheet:** Assets = Liabilities + Equity. As of a specific date.
   - **Profit & Loss:** Income - Expenses = Net Profit. Date range filter.
   - All reports: date range picker, CSV export

5. **Sidebar Menu**
   - Top-level "Double-Entry" with icon `balance`
   - Children: Chart of Accounts, Journal Entries, General Ledger, Trial Balance, Balance Sheet, P&L, Account Defaults

### Files

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
│   └── views/
│       ├── accounts/ (index, create, edit, import)
│       ├── journals/ (index, create, show, edit)
│       ├── general-ledger/index.blade.php
│       ├── trial-balance/index.blade.php
│       ├── balance-sheet/index.blade.php
│       ├── profit-loss/index.blade.php
│       └── account-defaults/index.blade.php
├── Routes/
│   ├── admin.php
│   └── portal.php (empty, required by framework)
├── Services/
│   └── AccountBalanceService.php
├── module.json
└── composer.json
```

### Acceptance Criteria & Commit Checkpoints

**AC1: Module scaffolding + migrations + seeder** 
- Module installs without errors
- Tables created with correct column types (unsignedInteger for company_id!)
- Default COA seeded (40+ accounts)
- Permissions registered for admin role
- Sidebar appears with all menu items
- Commit: `feat(double-entry): scaffold, migrations, default COA seed`

**AC2: Chart of Accounts CRUD**
- List page loads, grouped by type
- Can create account with code, name, type
- Can edit account
- Can soft-delete account (only if no journal lines)
- Parent/child hierarchy displays with indentation
- Enable/disable toggle works
- Commit: `feat(double-entry): chart of accounts CRUD`

**AC3: Journal Entries CRUD**
- Create journal entry with 2+ debit/credit lines
- Validation rejects unbalanced entries
- Auto-generates reference number (JE-0001, JE-0002...)
- List page shows entries with status
- Post/unpost works
- Can only edit/delete drafts
- Commit: `feat(double-entry): journal entries CRUD with balance validation`

**AC4: Reports**
- General Ledger: shows lines per account with running balance
- Trial Balance: shows all accounts, debits = credits
- Balance Sheet: Assets = Liabilities + Equity
- P&L: Income - Expenses = Net Profit
- Date range filters work on all reports
- Commit: `feat(double-entry): financial reports (GL, TB, BS, P&L)`

**AC5: Account Defaults**
- Settings page to map system types to COA accounts
- Saves and retrieves correctly
- Commit: `feat(double-entry): account defaults settings`

---

## Module 2: Bank Feeds

**Depends on:** Double-Entry module being installed and working.

### Database Tables

#### `nif_bank_feed_imports`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| bank_account_id | unsignedInteger, nullable, INDEX | Links to Double-Entry account |
| filename | varchar(255) | |
| original_filename | varchar(255) | |
| format | enum: csv, ofx, qfx | |
| row_count | unsignedInteger, default 0 | |
| status | enum: pending, processing, complete, failed | |
| column_mapping | json, nullable | Saved column mapping for reuse |
| error_message | text, nullable | |
| imported_at | timestamp, nullable | |
| created_at, updated_at | timestamps | |

#### `nif_bank_feed_rules`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| name | varchar(191) | Human-readable rule name |
| field | enum: description, amount, type | |
| operator | enum: contains, equals, starts_with, gt, lt, between | |
| value | varchar(255) | |
| value_end | varchar(255), nullable | For "between" operator |
| category_id | unsignedInteger, nullable, INDEX | Double-Entry account to assign |
| enabled | boolean, default true | |
| priority | unsignedInteger, default 0 | Lower = runs first |
| created_at, updated_at | timestamps | |

#### `nif_bank_feed_transactions`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| import_id | unsignedInteger, FK to imports | |
| bank_account_id | unsignedInteger, INDEX | |
| date | date | |
| description | varchar(500) | |
| amount | decimal(15,4) | |
| type | enum: deposit, withdrawal | |
| raw_data_json | json, nullable | Original CSV row |
| category_id | unsignedInteger, nullable, INDEX | Assigned Double-Entry account |
| matched_transaction_id | unsignedInteger, nullable | Linked Akaunting transaction |
| matched_journal_id | unsignedInteger, nullable | Linked journal entry |
| status | enum: pending, categorized, matched, ignored | |
| duplicate_hash | varchar(64), nullable, INDEX | SHA256 of date+amount+description |
| is_duplicate | boolean, default false | |
| match_confidence | decimal(5,2), nullable | 0-100 |
| created_at, updated_at | timestamps | |

#### `nif_bank_feed_reconciliations`
| Column | Type | Notes |
|--------|------|-------|
| id | unsignedInteger, AI, PK | |
| company_id | unsignedInteger, INDEX | |
| bank_account_id | unsignedInteger, INDEX | |
| period_start | date | |
| period_end | date | |
| opening_balance | decimal(15,4) | |
| closing_balance | decimal(15,4) | |
| status | enum: in_progress, completed | |
| completed_at | timestamp, nullable | |
| created_at, updated_at | timestamps | |

### Features

1. **CSV Import**
   - Upload CSV file
   - Column mapping UI: map CSV columns → date, description, amount, type (or credit/debit split)
   - Support common bank export formats (BoA, Chase, generic)
   - Save column mapping per bank account for reuse on future imports
   - Parse and import rows into `bank_feed_transactions`
   - Duplicate detection: SHA256 hash of date+amount+description

2. **OFX/QFX Import**
   - Parse OFX/QFX standard format (XML-based, used by most US banks)
   - Auto-detect transaction types (DEBIT/CREDIT)
   - Auto-map to date, description, amount fields

3. **Categorization Rules**
   - CRUD for rules: name, field, operator, value, target account
   - Priority ordering (drag-reorder or numeric)
   - Process: for each imported transaction, run rules in priority order
   - First match assigns the category (Double-Entry account)
   - Bulk apply rules to existing uncategorized transactions
   - "Apply Rules" button on import review page

4. **Transaction Matching**
   - Auto-match: compare imported transactions to existing Akaunting transactions by amount + date (±3 days)
   - Confidence scoring: exact amount = 70pts, date within 1 day = 20pts, date within 3 days = 10pts
   - Review UI: imported transaction on left, suggested matches on right
   - Actions: Accept match, Reject, Create new journal entry, Ignore
   - Bulk actions: auto-match all high-confidence (>80), ignore selected

5. **Reconciliation**
   - Select bank account + statement period (start/end date)
   - Enter opening and closing balance from bank statement
   - Match/unmatch transactions within the period
   - Show reconciliation difference (should be $0.00 when complete)
   - Mark period as reconciled
   - Reconciliation history

6. **Import History**
   - List past imports: date, filename, format, row count, status
   - Delete import (cascades to transactions)

7. **Sidebar Menu**
   - Top-level "Bank Feeds" with icon `account_balance`
   - Children: Import Transactions, Transaction Review, Categorization Rules, Reconciliation, Import History

### Files

```
modules/BankFeeds/
├── Database/
│   └── Migrations/
│       └── 2024_01_02_000001_create_bank_feed_tables.php
├── Http/
│   ├── Controllers/
│   │   ├── Imports.php
│   │   ├── Transactions.php
│   │   ├── Rules.php
│   │   ├── Matching.php
│   │   └── Reconciliation.php
│   └── Requests/
│       ├── ImportUpload.php
│       └── RuleStore.php
├── Listeners/
│   ├── AddAdminMenu.php
│   └── FinishInstallation.php
├── Models/
│   ├── Import.php
│   ├── Transaction.php
│   ├── Rule.php
│   └── Reconciliation.php
├── Providers/
│   ├── Event.php
│   └── Main.php
├── Resources/
│   ├── lang/en-GB/general.php
│   └── views/
│       ├── imports/ (index, create, map-columns)
│       ├── transactions/ (index, show)
│       ├── rules/ (index, create, edit)
│       ├── matching/ (index, show)
│       └── reconciliation/ (index, create, show)
├── Routes/
│   ├── admin.php
│   └── portal.php
├── Services/
│   ├── CsvParser.php
│   ├── OfxParser.php
│   ├── RuleEngine.php
│   └── TransactionMatcher.php
├── module.json
└── composer.json
```

### Acceptance Criteria & Commit Checkpoints

**AC6: Module scaffolding + migrations**
- Module installs without errors
- Tables created with correct types
- Permissions registered for admin role
- Sidebar appears
- Commit: `feat(bank-feeds): scaffold and migrations`

**AC7: CSV Import + Column Mapping**
- Upload CSV file
- Column mapping UI loads with detected columns
- Map columns → import runs → transactions appear in list
- Saved column mapping reused on next import for same bank account
- Duplicate detection flags repeat rows
- Commit: `feat(bank-feeds): CSV import with column mapping`

**AC8: OFX/QFX Import**
- Upload OFX file → auto-parsed → transactions imported
- Correct date, amount, type detection
- Commit: `feat(bank-feeds): OFX/QFX import support`

**AC9: Categorization Rules**
- Create rule: description contains "LUMBER" → account "5000 COGS Materials"
- Apply rules → matching transactions get categorized
- Priority ordering works (lower priority runs first)
- Bulk apply to existing uncategorized transactions
- Commit: `feat(bank-feeds): categorization rules engine`

**AC10: Transaction Matching**
- Auto-match finds existing Akaunting transactions by amount + date
- Review UI shows suggestions with confidence scores
- Accept/Reject/Create/Ignore actions work
- Commit: `feat(bank-feeds): transaction matching with confidence scoring`

**AC11: Reconciliation**
- Select bank account + period → enter opening/closing balance
- Match transactions → difference updates
- Mark reconciled when difference = $0.00
- Reconciliation history shows past periods
- Commit: `feat(bank-feeds): reconciliation workflow`

---

## Deployment Steps (per module)

```bash
# 1. Delete old module from source and Docker
rm -rf /home/valleybird/projects/akaunting-setup/modules/MODULE_NAME
docker exec akaunting rm -rf /var/www/html/modules/MODULE_NAME

# 2. Build new module (Codex writes to source)
# ... Codex builds here ...

# 3. Copy to Docker
docker cp /home/valleybird/projects/akaunting-setup/modules/MODULE_NAME akaunting:/var/www/html/modules/

# 4. Set permissions
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/MODULE_NAME

# 5. Run migrations
docker exec akaunting php artisan migrate --force

# 6. Enable module
docker exec akaunting php artisan module:enable module-alias

# 7. Trigger permission installation
docker exec akaunting php artisan tinker --execute="event(new \App\Events\Module\Installed('module-alias', '1'));"

# 8. Clear all caches
docker exec akaunting php artisan config:clear
docker exec akaunting php artisan cache:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan view:clear

# 9. Verify
docker exec akaunting curl -s -o /dev/null -w "%{http_code}" http://localhost/1/MODULE_ROUTE/
# Should be 200 (or 302 redirect to login)
```

## Verification Protocol

After each module deploys:
1. Open browser: `http://SERVER:8085/1/MODULE_ROUTE`
2. Verify page loads without 403/404/500
3. Test CRUD: create, view, edit, delete
4. Check sidebar: correct menu items, no duplicates
5. Check database: verify records created with correct company_id scoping
6. Test with sample data (create accounts, post journal entries, import CSV)

**DO NOT proceed to the next module until the current one is fully verified.**

## Task Splitting for Codex

Split into atomic tasks for fresh Codex context windows:

### Task 01: Pre-clean + Double-Entry scaffold + migrations + seeder + permissions
- Run all pre-clean SQL
- Build module skeleton, providers, module.json
- Create migration with correct types
- Create DefaultAccounts seeder
- Create FinishInstallation + AddAdminMenu listeners
- Create routes (admin.php + empty portal.php)
- Deploy and verify: module enables, sidebar appears, COA seeded

### Task 02: Double-Entry — Chart of Accounts CRUD
- Build Accounts controller, model, form requests
- Build views: index (grouped by type), create, edit
- CSV import view + controller action
- Test: create, edit, delete, list accounts

### Task 03: Double-Entry — Journal Entries CRUD
- Build Journals controller, JournalLine model, form requests
- Build views: index, create (with dynamic debit/credit lines), show, edit
- Balance validation (debits = credits)
- Auto-reference numbering
- Post/unpost logic

### Task 04: Double-Entry — Reports + Account Defaults
- Build all 4 report controllers + views
- AccountBalanceService for calculations
- AccountDefaults controller + view
- Date range filters, CSV export

### Task 05: Bank Feeds scaffold + migrations + permissions + CSV import
- Build module skeleton
- Create migration
- Build CSV upload + column mapping UI + parser
- Duplicate detection
- Import history

### Task 06: Bank Feeds — OFX/QFX import + categorization rules
- OFX parser service
- Rules CRUD + engine
- Bulk apply rules

### Task 07: Bank Feeds — Matching + Reconciliation
- TransactionMatcher service
- Matching UI with suggestions
- Reconciliation workflow
- Dashboard integration

## Test Data

For verification, use this sample CSV (Bank of America format):
```csv
Date,Description,Amount
03/15/2026,CARTER LUMBER #1234,-1250.00
03/16/2026,DEPOSIT - CHECK #1005,5000.00
03/17/2026,HOME DEPOT #4567,-327.45
03/18/2026,SHELL GAS STATION,-65.00
03/20/2026,ACH DEPOSIT - ADAM V,15000.00
03/21/2026,INSURANCE PAYMENT,-450.00
03/22/2026,ATM WITHDRAWAL,-200.00
03/23/2026,ABC SUPPLY,-892.30
03/24/2026,SUBCONTRACTOR - CARLOS CRUZ,-9450.00
03/25/2026,DEPOSIT - CHECK #1006,6421.15
```
