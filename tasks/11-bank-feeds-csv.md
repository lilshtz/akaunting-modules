# Task 11: Bank Feeds Module — CSV/OFX Import + Categorization Rules

## Context
Phase 1 of Bank Feeds — import and categorize. Matching comes in Task 12.

## Objective
Import bank transactions from CSV/OFX files with auto-categorization rules.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/BankFeeds/`

### 2. Database
- `bank_feed_imports` — id, company_id, bank_account_id (FK), filename, format (csv/ofx/qfx), row_count, status (pending/processing/complete/failed), imported_at, created_at
- `bank_feed_rules` — id, company_id, field (enum: 'description','vendor','amount'), operator (enum: 'contains','equals','starts_with','gt','lt','between'), value (varchar), category_id (FK nullable), vendor_id (FK nullable), enabled (boolean), priority (int), created_at, updated_at
- `bank_feed_transactions` — id, import_id, bank_account_id, date, description, amount, type (deposit/withdrawal), raw_data_json, category_id, vendor_id, matched_transaction_id (nullable), status (pending/categorized/matched/ignored), created_at

### 3. CSV Import
- Upload CSV file
- Column mapping UI: map CSV columns to fields (date, description, amount, type/credit/debit)
- Support common bank formats (BoA, Chase, generic)
- Save column mapping per bank account for reuse
- Parse and import rows into bank_feed_transactions

### 4. OFX/QFX Import
- Parse OFX/QFX standard format (used by most US banks)
- Auto-detect date format and transaction types

### 5. Categorization Rules Engine
- Rules UI: create/edit/delete/reorder rules
- Process: for each imported transaction, run rules in priority order
- First matching rule assigns category and/or vendor
- Default rule: uncategorized
- Bulk apply rules to existing uncategorized transactions

### 6. Import History
- List past imports with date, file, row count, status
- Re-import / delete import

### 7. Settings
- Per-bank-account column mappings
- Default categorization rules

### 8. Sidebar
Add "Bank Feeds" under Banking section.

## Verification
1. Upload BoA CSV → column mapping UI → import transactions
2. Transactions appear in bank feed list with date, description, amount
3. Create rule: description contains "LUMBER" → category "Materials"
4. Re-process transactions → matching ones get categorized
5. OFX file imports correctly
6. Import history shows past imports

## Commit Message
`feat(modules): bank feeds with CSV/OFX import and categorization rules`
