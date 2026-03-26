# Task 02: Journal Entry Engine + Auto-Posting

## Context
The DoubleEntry module scaffold and COA exist from Task 01. Now build the journal entry system — both manual entries and auto-posting from core Akaunting events (invoice/bill/payment creation).

## Objective
Implement manual journal entries and automatic journal posting when invoices, bills, payments, and transfers are created/updated in Akaunting core.

## What to Build

### 1. Database Migrations

**Table: `double_entry_journals`**
- `id` — bigint unsigned PK
- `company_id` — bigint unsigned FK
- `date` — date
- `reference` — varchar(100) nullable
- `description` — text nullable
- `basis` — enum: 'accrual', 'cash' — default 'accrual'
- `status` — enum: 'draft', 'posted' — default 'posted'
- `documentable_type` — varchar(255) nullable (polymorphic: invoice, bill, payment, transfer)
- `documentable_id` — bigint unsigned nullable
- `created_by` — bigint unsigned nullable FK to users
- `created_at`, `updated_at`, `deleted_at`

**Table: `double_entry_journal_lines`**
- `id` — bigint unsigned PK
- `journal_id` — bigint unsigned FK
- `account_id` — bigint unsigned FK to double_entry_accounts
- `debit` — decimal(15,4) default 0
- `credit` — decimal(15,4) default 0
- `description` — text nullable

### 2. Models
- `Models/Journal.php` — belongs to Company, has many JournalLines, morphTo documentable
- `Models/JournalLine.php` — belongs to Journal, belongs to Account

### 3. Manual Journal Entry Controller
`Http/Controllers/Journals.php`:
- `index()` — List all journal entries with filters (date range, reference, status)
- `create()` — Form with dynamic line items (add rows: account, debit, credit, description)
- `store()` — Validate: total debits MUST equal total credits. Save journal + lines.
- `show($id)` — View journal entry detail
- `edit($id)` — Edit (only draft entries)
- `update($id)` — Validate and update
- `destroy($id)` — Delete (only draft entries, or reverse posted entries)
- `duplicate($id)` — Copy an existing entry as new draft

### 4. Validation
- Total debits must equal total credits (to the penny)
- At least 2 lines per entry
- Each line must have either debit > 0 OR credit > 0 (not both, not zero)
- Account must exist and belong to same company
- Date required

### 5. Auto-Posting Listeners
Register event listeners in `Providers/Event.php` that hook into Akaunting core events:

**On Invoice Created/Updated:**
- Debit: Accounts Receivable
- Credit: Sales Revenue (or per-item category accounts if mapped)
- Credit: Tax Payable (if tax exists)

**On Bill Created/Updated:**
- Debit: Expense account (per category)
- Credit: Accounts Payable

**On Payment Received (invoice payment):**
- Debit: Bank/Cash account
- Credit: Accounts Receivable

**On Payment Made (bill payment):**
- Debit: Accounts Payable
- Credit: Bank/Cash account

**On Transfer:**
- Debit: Destination bank account
- Credit: Source bank account

Use Account Defaults table to resolve which COA account maps to AR, AP, Sales, etc. If no default set, skip auto-posting (log warning).

### 6. Views
- `views/journals/index.blade.php` — Sortable table with columns: Date, Reference, Description, Debit Total, Credit Total, Status
- `views/journals/create.blade.php` — Form with dynamic line item rows, running totals, balance check
- `views/journals/show.blade.php` — Read-only view of entry with all lines
- `views/journals/edit.blade.php` — Edit form

### 7. Routes
Add to existing `Routes/admin.php`:
```php
Route::resource('journals', 'Journals');
Route::post('journals/{id}/duplicate', 'Journals@duplicate')->name('double-entry.journals.duplicate');
```

### 8. Recurring Journals
- Add `recurring_frequency` (null, weekly, monthly, quarterly, yearly) and `next_recurring_date` to journals table
- Artisan command `double-entry:process-recurring` that creates new entries from recurring templates
- Register in scheduler

## Files to Create/Modify
- `Database/Migrations/` — new migration for journals and journal_lines tables
- `Models/Journal.php`, `Models/JournalLine.php`
- `Http/Controllers/Journals.php`
- `Http/Requests/JournalStore.php`, `Http/Requests/JournalUpdate.php`
- `Listeners/` — DocumentCreated, DocumentUpdated, TransactionCreated, TransferCreated
- `Providers/Event.php` — register listeners
- `Resources/views/journals/` — index, create, show, edit
- `Routes/admin.php` — add journal routes

## Verification
1. Create manual journal entry with 2+ lines — debits equal credits — saves successfully
2. Create journal with unbalanced debits/credits — validation error
3. Create an invoice in Akaunting core — auto-generates journal entry (DR: AR, CR: Revenue)
4. Record payment on invoice — auto-generates journal (DR: Bank, CR: AR)
5. Create a bill — auto-generates journal (DR: Expense, CR: AP)
6. Make a transfer — auto-generates journal (DR: Dest, CR: Source)
7. Journal index shows all entries sorted by date

## Commit Message
`feat(modules): journal entry engine with auto-posting from invoices/bills/payments`
