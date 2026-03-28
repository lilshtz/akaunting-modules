# Task 07: Bank Feeds — Transaction Matching + Reconciliation

## Context
Bank Feeds has CSV/OFX import and categorization rules working. Now add transaction matching (linking bank transactions to journal entries) and reconciliation workflow.

## Environment
- Module source: `/home/valleybird/projects/akaunting-setup/modules/BankFeeds/`
- Docker: `akaunting` on port 8085
- Existing tables: nif_bank_feed_transactions, nif_bank_feed_imports, nif_bank_feed_rules
- Need to add: nif_bank_feed_reconciliations (if not already created in Task 05 migration)

## What to Build

### 1. Transaction Matcher Service (`Services/TransactionMatcher.php`)

Match imported bank transactions to existing Double-Entry journal entries.

**Matching algorithm:**
```
For each bank_feed_transaction (status = 'pending' or 'categorized'):
  Search nif_double_entry_journal_lines WHERE:
    - journal.company_id = current company
    - journal.status = 'posted'
    - ABS(line.debit - line.credit) matches ABS(transaction.amount) within $0.01
    - journal.date is within ±3 days of transaction.date
    - NOT already matched to another bank_feed_transaction
  
  Score each candidate:
    - Exact amount match: 50 points
    - Date exact match: 30 points
    - Date within 1 day: 20 points
    - Date within 3 days: 10 points
    - Description similarity (Levenshtein or str_contains on account name): 20 points
  
  Return top 3 matches sorted by score DESC
  If top match score > 80: flag as high-confidence auto-match
```

**Methods:**
- `findMatches($transaction)` → returns array of {journal_id, journal_line_id, score, journal}
- `autoMatchAll($companyId)` → auto-match all high-confidence transactions, return count matched
- `acceptMatch($transactionId, $journalId)` → link transaction to journal, set status='matched'
- `rejectMatch($transactionId)` → clear any suggested match
- `createJournalFromTransaction($transaction, $accountId)` → create a new journal entry from an unmatched bank transaction

### 2. Matching Controller (`Http/Controllers/Matching.php`)
- `index()` — List unmatched transactions with suggested matches
- `show($id)` — Show single transaction with match suggestions
- `accept($id)` — Accept a suggested match (POST: journal_id in request)
- `reject($id)` — Reject suggestion, keep as unmatched
- `createTransaction($id)` — Create new journal entry from bank transaction
- `autoMatch()` — POST: run auto-match on all pending transactions
- `bulkIgnore()` — POST: ignore selected transactions

### 3. Matching Views

**`Resources/views/matching/index.blade.php`:**
- Summary bar: X unmatched, Y high-confidence, Z ignored
- "Auto-Match All" button (matches all >80 confidence)
- Table: Date, Description, Amount, Type, Top Match (journal ref + score), Status, Actions
- Actions: View Matches, Accept Top, Ignore
- Filter: status (unmatched/matched/ignored), confidence threshold

**`Resources/views/matching/show.blade.php`:**
- Left panel: Bank transaction details (date, description, amount, type, raw data)
- Right panel: Suggested matches (up to 3), each showing:
  - Journal reference, date, description, amount
  - Confidence score (color-coded: green >80, yellow 50-80, red <50)
  - "Accept" button
- Bottom actions: "Create New Journal Entry", "Ignore Transaction"
- "Create New" form: select debit/credit accounts from Double-Entry COA

### 4. Reconciliation Model (`Models/Reconciliation.php`)
- Table: `bank_feed_reconciliations`
- Fillable: company_id, bank_account_id, period_start, period_end, opening_balance, closing_balance, status, completed_at
- Relationships: transactions() (matched transactions within the period)

### 5. Reconciliation Controller (`Http/Controllers/Reconciliation.php`)
- `index()` — List past reconciliations + "New Reconciliation" button
- `create()` — Form: select bank account, period start/end, opening balance, closing balance
- `store()` — Create reconciliation record
- `show($id)` — Reconciliation workspace:
  - Show all matched transactions in the period
  - Running total: Opening Balance + Deposits - Withdrawals = Computed Balance
  - Difference = Computed Balance - Closing Balance (entered by user)
  - When difference = $0.00, show "Mark as Reconciled" button
- `complete($id)` — Mark reconciliation as completed

### 6. Reconciliation Views

**`Resources/views/reconciliation/index.blade.php`:**
- Table: Bank Account, Period, Opening Balance, Closing Balance, Status, Actions
- "New Reconciliation" button

**`Resources/views/reconciliation/create.blade.php`:**
- Form: Bank Account (dropdown from Double-Entry asset accounts), Period Start, Period End, Opening Balance, Closing Balance

**`Resources/views/reconciliation/show.blade.php`:**
- Header: Bank Account, Period, Opening/Closing Balance
- Table of matched transactions in period: Date, Description, Deposits, Withdrawals
- Running totals at bottom
- Difference indicator (green $0.00 = balanced, red if not)
- "Mark Reconciled" button (only enabled when difference = $0.00)

### 7. Additional Routes
```php
// Add to existing bank-feeds route group:
Route::get('matching', 'Matching@index')->name('matching.index');
Route::post('matching/auto-match', 'Matching@autoMatch')->name('matching.auto-match');
Route::post('matching/bulk-ignore', 'Matching@bulkIgnore')->name('matching.bulk-ignore');
Route::get('matching/{id}', 'Matching@show')->name('matching.show');
Route::post('matching/{id}/accept', 'Matching@accept')->name('matching.accept');
Route::post('matching/{id}/reject', 'Matching@reject')->name('matching.reject');
Route::post('matching/{id}/create-journal', 'Matching@createJournal')->name('matching.create-journal');

Route::get('reconciliation', 'Reconciliation@index')->name('reconciliation.index');
Route::get('reconciliation/create', 'Reconciliation@create')->name('reconciliation.create');
Route::post('reconciliation', 'Reconciliation@store')->name('reconciliation.store');
Route::get('reconciliation/{id}', 'Reconciliation@show')->name('reconciliation.show');
Route::post('reconciliation/{id}/complete', 'Reconciliation@complete')->name('reconciliation.complete');
```

### 8. Update Permissions
Add to FinishInstallation:
```php
'bank-feeds-matching' => 'c,r,u,d',
'bank-feeds-reconciliation' => 'c,r,u,d',
```

### 9. Update Sidebar
Add children: "Transaction Matching", "Reconciliation" under Bank Feeds menu.

## Deploy & Verify
```bash
docker cp /home/valleybird/projects/akaunting-setup/modules/BankFeeds akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/BankFeeds
docker exec akaunting php artisan migrate --force
docker exec akaunting php artisan tinker --execute="event(new \App\Events\Module\Installed('bank-feeds', '1'));"
docker exec akaunting php artisan view:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan cache:clear

# Test
for page in matching reconciliation; do
  echo -n "$page: "
  curl -s -o /dev/null -w "%{http_code}" "http://100.83.12.126:8085/1/bank-feeds/$page"
  echo ""
done
```

## Success Criteria
- [ ] Matching page shows unmatched transactions with suggestions
- [ ] Auto-match correctly links high-confidence transactions
- [ ] Accept/reject actions work
- [ ] Can create new journal entry from unmatched transaction
- [ ] Reconciliation CRUD works
- [ ] Reconciliation workspace shows running totals
- [ ] Can mark reconciliation complete when balanced ($0 difference)
- [ ] Reconciliation history shows past periods
- [ ] All new permissions registered

## Commit
`feat(bank-feeds): transaction matching and reconciliation workflow`
