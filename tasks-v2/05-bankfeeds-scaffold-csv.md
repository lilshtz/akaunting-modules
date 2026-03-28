# Task 05: Bank Feeds — Scaffold + Migrations + CSV Import

## Context
Double-Entry module is complete. Now build Bank Feeds module — starting with scaffold, migrations, and CSV import functionality. This module lets users import bank statements and categorize transactions.

## Pre-Clean
```bash
# Drop old Bank Feeds tables
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "
DROP TABLE IF EXISTS nif_bank_feed_transactions;
DROP TABLE IF EXISTS nif_bank_feed_rules;
DROP TABLE IF EXISTS nif_bank_feed_reconciliations;
DROP TABLE IF EXISTS nif_bank_feed_imports;
DELETE FROM nif_role_permissions WHERE permission_id IN (SELECT id FROM nif_permissions WHERE name LIKE '%bank-feed%');
DELETE FROM nif_permissions WHERE name LIKE '%bank-feed%';
DELETE FROM nif_migrations WHERE migration LIKE '%bank_feed%';
"

# Remove old module
docker exec akaunting rm -rf /var/www/html/modules/BankFeeds
rm -rf /home/valleybird/projects/akaunting-setup/modules/BankFeeds
```

## Environment
- Module source: `/home/valleybird/projects/akaunting-setup/modules/BankFeeds/`
- Docker: `akaunting` on port 8085
- DB prefix: `nif_`
- Test URL: `http://100.83.12.126:8085/1/bank-feeds/`
- Double-Entry module must be enabled (dependency for account references)

## What to Build

### Module Structure
```
modules/BankFeeds/
├── Database/Migrations/2024_01_02_000001_create_bank_feed_tables.php
├── Http/
│   ├── Controllers/
│   │   ├── Imports.php
│   │   ├── Transactions.php
│   │   └── Rules.php
│   └── Requests/
│       ├── ImportUpload.php
│       └── RuleStore.php
├── Listeners/
│   ├── AddAdminMenu.php
│   └── FinishInstallation.php
├── Models/
│   ├── Import.php
│   ├── Transaction.php
│   └── Rule.php
├── Providers/
│   ├── Event.php
│   └── Main.php
├── Resources/
│   ├── lang/en-GB/general.php
│   └── views/
│       ├── imports/ (index, create, map-columns)
│       └── transactions/ (index)
├── Routes/
│   ├── admin.php
│   └── portal.php
├── Services/
│   └── CsvParser.php
├── module.json
└── composer.json
```

### module.json
```json
{
    "alias": "bank-feeds",
    "icon": "account_balance",
    "version": "1.0.0",
    "active": 1,
    "providers": [
        "Modules\\BankFeeds\\Providers\\Event",
        "Modules\\BankFeeds\\Providers\\Main"
    ],
    "aliases": {},
    "files": [],
    "requires": ["double-entry"],
    "reports": [],
    "widgets": [],
    "settings": [],
    "extra-modules": {},
    "routes": {}
}
```

### Migration — ALL columns use unsignedInteger (NOT bigInteger)

**`nif_bank_feed_imports`:**
- id (unsignedInteger AI PK)
- company_id (unsignedInteger INDEX)
- bank_account_id (unsignedInteger nullable INDEX) — references Double-Entry account, NO FK constraint
- filename (varchar 255)
- original_filename (varchar 255)
- format (enum: csv, ofx, qfx)
- row_count (unsignedInteger default 0)
- status (enum: pending, processing, complete, failed)
- column_mapping (json nullable) — saved mapping for reuse
- error_message (text nullable)
- imported_at (timestamp nullable)
- timestamps

**`nif_bank_feed_rules`:**
- id (unsignedInteger AI PK)
- company_id (unsignedInteger INDEX)
- name (varchar 191)
- field (enum: description, amount, type)
- operator (enum: contains, equals, starts_with, gt, lt, between)
- value (varchar 255)
- value_end (varchar 255 nullable) — for "between" operator
- category_id (unsignedInteger nullable INDEX) — Double-Entry account, NO FK
- enabled (boolean default true)
- priority (unsignedInteger default 0)
- timestamps

**`nif_bank_feed_transactions`:**
- id (unsignedInteger AI PK)
- company_id (unsignedInteger INDEX)
- import_id (unsignedInteger, FK to imports)
- bank_account_id (unsignedInteger INDEX)
- date (date)
- description (varchar 500)
- amount (decimal 15,4)
- type (enum: deposit, withdrawal)
- raw_data_json (json nullable)
- category_id (unsignedInteger nullable INDEX)
- matched_journal_id (unsignedInteger nullable INDEX) — links to Double-Entry journal
- status (enum: pending, categorized, matched, ignored)
- duplicate_hash (varchar 64 nullable INDEX)
- is_duplicate (boolean default false)
- timestamps

### Permissions (FinishInstallation)
```php
$this->attachPermissionsToAdminRoles([
    'bank-feeds-imports' => 'c,r,u,d',
    'bank-feeds-transactions' => 'c,r,u,d',
    'bank-feeds-rules' => 'c,r,u,d',
]);
```

### Sidebar (AddAdminMenu)
Top-level "Bank Feeds" with icon `account_balance`. Children:
- Import Transactions
- Transaction Review
- Categorization Rules
- Import History

### CSV Import Flow

**Step 1: Upload** (`imports/create.blade.php`)
- File upload form (accept .csv)
- Select bank account (dropdown from Double-Entry accounts, filtered to asset type)
- Upload button

**Step 2: Column Mapping** (`imports/map-columns.blade.php`)
- After upload, detect CSV columns (first row = headers)
- Show mapping UI: for each required field (Date, Description, Amount, Type), show dropdown of CSV columns
- Support two amount formats:
  - Single "Amount" column (negative = withdrawal, positive = deposit)
  - Separate "Debit" and "Credit" columns
- "Import" button to process

**Step 3: Processing** (in controller)
- Parse CSV using mapped columns
- For each row:
  - Determine date, description, amount, type
  - Generate duplicate_hash: SHA256 of `{date}|{amount}|{description}`
  - Check for existing transaction with same hash → flag as duplicate
  - Create Transaction record with status 'pending'
- Update Import record: row_count, status='complete', imported_at
- If bank_account_id provided, save column_mapping for reuse

**CsvParser Service:**
- `parseHeaders($filepath)` → returns array of column names
- `parseRows($filepath, $mapping)` → returns array of parsed transaction data
- Handle common CSV quirks: quoted fields, different date formats (MM/DD/YYYY, YYYY-MM-DD, MM-DD-YYYY), currency symbols in amounts

### Import History (`imports/index.blade.php`)
- Table: Date, Filename, Format, Rows, Status, Actions (delete)
- Delete cascades to related transactions

### Transaction Review (`transactions/index.blade.php`)
- Table: Date, Description, Amount, Type, Category, Status, Actions
- Filter by: status (pending/categorized/matched/ignored), import, date range
- Bulk actions: ignore selected

### Routes
```php
Route::admin('bank-feeds', function () {
    Route::get('imports', 'Imports@index')->name('imports.index');
    Route::get('imports/create', 'Imports@create')->name('imports.create');
    Route::post('imports/upload', 'Imports@upload')->name('imports.upload');
    Route::get('imports/{id}/map', 'Imports@mapColumns')->name('imports.map');
    Route::post('imports/{id}/process', 'Imports@process')->name('imports.process');
    Route::delete('imports/{id}', 'Imports@destroy')->name('imports.destroy');
    
    Route::get('transactions', 'Transactions@index')->name('transactions.index');
    Route::patch('transactions/{id}/ignore', 'Transactions@ignore')->name('transactions.ignore');
    Route::post('transactions/bulk-ignore', 'Transactions@bulkIgnore')->name('transactions.bulk-ignore');
    
    Route::resource('rules', 'Rules');
    Route::post('rules/apply', 'Rules@apply')->name('rules.apply');
}, ['namespace' => 'Modules\BankFeeds\Http\Controllers']);
```

## Deploy & Verify
```bash
docker cp /home/valleybird/projects/akaunting-setup/modules/BankFeeds akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/BankFeeds
docker exec akaunting php artisan migrate --force
docker exec akaunting php artisan module:enable bank-feeds
docker exec akaunting php artisan tinker --execute="event(new \App\Events\Module\Installed('bank-feeds', '1'));"
docker exec akaunting php artisan config:clear
docker exec akaunting php artisan cache:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan view:clear

# Verify
docker exec akaunting-db mariadb -u akaunting -pakaunting_dev akaunting -e "SHOW TABLES LIKE 'nif_bank_feed%';"
docker exec akaunting php artisan route:list --name=bank-feeds
curl -s -o /dev/null -w "%{http_code}" http://100.83.12.126:8085/1/bank-feeds/imports
```

## Test CSV Data
Create a test file at `/home/valleybird/projects/akaunting-setup/test-data/boa-march-2026.csv`:
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

## Success Criteria
- [ ] Module installs, tables created, permissions registered
- [ ] Sidebar shows "Bank Feeds" with child items
- [ ] Can upload a CSV file
- [ ] Column mapping UI detects and shows CSV headers
- [ ] Import processes all rows correctly
- [ ] Duplicate detection works (re-importing same file flags duplicates)
- [ ] Transaction list shows imported data with filters
- [ ] Import history shows past imports with delete option
- [ ] Rules CRUD works (Task 06 will add the rule engine logic)

## Commit
`feat(bank-feeds): scaffold, migrations, CSV import with column mapping`
