# Task 03: Double-Entry — Journal Entries CRUD

## Context
Chart of Accounts CRUD is built (Task 02). Now build Journal Entries — the core of double-entry accounting. Every financial transaction is recorded as a journal entry with balanced debit/credit lines.

## Environment
- Module source: `/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/`
- Docker: `akaunting` on port 8085, DB: `akaunting-db`
- Test URL: `http://100.83.12.126:8085/1/double-entry/journals`
- Tables already exist: `nif_double_entry_journals`, `nif_double_entry_journal_lines`

## What to Build

### 1. Journals Controller (`Http/Controllers/Journals.php`)
Extend `App\Abstracts\Http\Controller`. Implement:
- `index()` — List all journal entries. Columns: Date, Reference, Description, Total Amount, Status (draft/posted), Actions
- `create()` — Form with dynamic debit/credit line items
- `store()` — Validate (MUST check debits = credits), save journal + lines in a DB transaction
- `show($id)` — Read-only view of journal entry with all lines
- `edit($id)` — Edit form (only for draft entries)
- `update($id)` — Validate and update (only drafts)
- `destroy($id)` — Delete (only drafts)

Add custom routes:
- `POST journals/{journal}/post` — Change status from draft to posted
- `POST journals/{journal}/unpost` — Change status from posted back to draft

### 2. Form Requests
**`Http/Requests/JournalStore.php`:**
- date: required, date
- reference: nullable, string, max:100
- description: nullable, string
- basis: required, in:accrual,cash
- status: required, in:draft,posted
- lines: required, array, min:2
- lines.*.account_id: required, exists in double_entry_accounts (scoped by company)
- lines.*.debit: required_without:lines.*.credit, numeric, min:0
- lines.*.credit: required_without:lines.*.debit, numeric, min:0
- lines.*.description: nullable, string
- Custom validation rule: sum of all debits MUST equal sum of all credits

**`Http/Requests/JournalUpdate.php`:**
Same as Store.

### 3. Journal Model (`Models/Journal.php`)
- Table: `double_entry_journals`
- Fillable: company_id, date, reference, description, basis, status, documentable_type, documentable_id, recurring_frequency, next_recurring_date, created_by
- Casts: date → date, status → string
- Relationships: lines(), creator()
- Scoped by company_id
- `getTotalAttribute()` — sum of debit amounts from lines
- `isBalanced()` — returns true if sum(debits) == sum(credits)
- `isEditable()` — returns true if status is 'draft'
- Auto-generate reference: JE-0001, JE-0002, etc. (padded to 4 digits, auto-increment per company)

### 4. JournalLine Model (`Models/JournalLine.php`)
- Table: `double_entry_journal_lines`
- Fillable: journal_id, account_id, debit, credit, description
- Casts: debit → decimal:4, credit → decimal:4
- Relationships: journal(), account()

### 5. Views

**`Resources/views/journals/index.blade.php`:**
- "Add New" button
- Table: Date, Reference, Description, Total, Status (badge: green=posted, yellow=draft), Actions
- Sort by date descending
- Use Akaunting table components

**`Resources/views/journals/create.blade.php`:**
- Header fields: Date, Reference (auto-filled but editable), Description, Basis (Accrual/Cash dropdown)
- Dynamic line items section:
  - Each line: Account (dropdown of all COA accounts), Debit (number input), Credit (number input), Description
  - "Add Line" button to add more rows (JavaScript)
  - Running totals at bottom: Total Debits, Total Credits, Difference
  - Visual indicator when unbalanced (red) vs balanced (green)
- Save as Draft / Post buttons
- Use simple JavaScript for dynamic rows (no framework needed — just DOM manipulation)

**`Resources/views/journals/show.blade.php`:**
- Read-only display of journal header + line items
- Post/Unpost button (if applicable)
- Edit button (if draft)
- Back to list

**`Resources/views/journals/edit.blade.php`:**
- Same as create but pre-populated
- Only accessible for draft entries

### 6. Store Logic (in controller or service)
```php
DB::transaction(function () {
    // 1. Auto-generate reference if blank
    // 2. Create journal record
    // 3. Create all journal lines
    // 4. Verify balanced (safety check)
});
```

## Deploy & Verify
```bash
docker cp /home/valleybird/projects/akaunting-setup/modules/DoubleEntry akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/DoubleEntry
docker exec akaunting php artisan view:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan cache:clear

# Test
curl -s -o /dev/null -w "%{http_code}" http://100.83.12.126:8085/1/double-entry/journals
curl -s -o /dev/null -w "%{http_code}" http://100.83.12.126:8085/1/double-entry/journals/create
```

## Success Criteria
- [ ] Journal list page loads with sortable columns
- [ ] Can create a journal entry with 2+ debit/credit lines
- [ ] Balance validation rejects unbalanced entries (server-side)
- [ ] Client-side shows running totals and balance indicator
- [ ] Reference auto-generates (JE-0001, JE-0002...)
- [ ] Post/unpost toggle works
- [ ] Can only edit/delete draft entries
- [ ] Show page displays full journal with line items

## Commit
`feat(double-entry): journal entries CRUD with balance validation`
