# Task 06: Bank Feeds — OFX/QFX Import + Categorization Rules Engine

## Context
Bank Feeds module has CSV import working (Task 05). Now add OFX/QFX file support and the categorization rules engine.

## Environment
- Module source: `/home/valleybird/projects/akaunting-setup/modules/BankFeeds/`
- Docker: `akaunting` on port 8085
- Tables exist: nif_bank_feed_imports, nif_bank_feed_transactions, nif_bank_feed_rules

## What to Build

### 1. OFX Parser Service (`Services/OfxParser.php`)
OFX/QFX files are SGML-like (not strict XML). Parse manually:

```
OFXHEADER:100
DATA:OFXSGML
...
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT
            <DTPOSTED>20260315
            <TRNAMT>-1250.00
            <NAME>CARTER LUMBER
            <MEMO>Purchase
          </STMTTRN>
          ...
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
```

**Methods:**
- `parse($filepath)` → returns array of transactions: date, description, amount, type
- Extract from `<STMTTRN>` blocks: TRNTYPE, DTPOSTED (YYYYMMDD format), TRNAMT, NAME/MEMO
- TRNTYPE: DEBIT → withdrawal, CREDIT → deposit, POS → withdrawal, XFER → check sign of TRNAMT
- Handle both OFX and QFX (QFX is Quicken's version, same format)

### 2. Update Import Controller
- Modify `Imports@create` to accept .csv, .ofx, .qfx files
- Modify `Imports@upload` to detect format from file extension
- For OFX/QFX: skip column mapping step, go straight to processing
- For CSV: continue with existing column mapping flow

### 3. Categorization Rules Engine (`Services/RuleEngine.php`)

**Methods:**
- `applyRules($transactions, $companyId)` — apply all enabled rules to a collection of transactions
- `matchRule($rule, $transaction)` — check if a single rule matches a transaction

**Rule matching logic:**
```
For each transaction (status = 'pending'):
  For each rule (enabled=true, ordered by priority ASC):
    Match based on field + operator + value:
      - field=description, operator=contains: stripos(transaction.description, rule.value) !== false
      - field=description, operator=equals: strtolower(transaction.description) == strtolower(rule.value)
      - field=description, operator=starts_with: str_starts_with(strtolower(transaction.description), strtolower(rule.value))
      - field=amount, operator=gt: abs(transaction.amount) > rule.value
      - field=amount, operator=lt: abs(transaction.amount) < rule.value
      - field=amount, operator=between: abs(transaction.amount) between rule.value and rule.value_end
      - field=type, operator=equals: transaction.type == rule.value
    If match:
      Set transaction.category_id = rule.category_id
      Set transaction.status = 'categorized'
      Break (first match wins)
```

### 4. Rules Controller (`Http/Controllers/Rules.php`)
Full CRUD:
- `index()` — List all rules, ordered by priority
- `create()` — Form: name, field (dropdown), operator (dropdown, changes based on field), value, value_end (only for "between"), category (dropdown of Double-Entry accounts), enabled, priority
- `store()` — Save rule
- `edit($id)` / `update($id)` — Edit rule
- `destroy($id)` — Delete rule
- `apply()` — POST action: run all rules against all pending/uncategorized transactions. Report how many were categorized.

### 5. Rules Views

**`Resources/views/rules/index.blade.php`:**
- Table: Priority, Name, Field, Operator, Value, Target Account, Enabled, Actions
- "Add Rule" button
- "Apply All Rules" button (runs engine against uncategorized transactions)
- Ordered by priority

**`Resources/views/rules/create.blade.php`:**
- Form fields: Name, Field (dropdown), Operator (dropdown), Value, Value End (shown only when operator=between), Target Account (dropdown from Double-Entry accounts), Priority (number), Enabled (checkbox)
- JavaScript: when Field changes, update Operator dropdown options

**`Resources/views/rules/edit.blade.php`:**
- Same as create, pre-populated

### 6. Integration with Import Flow
After CSV/OFX import completes, automatically run the rules engine against newly imported transactions:
```php
// In Imports controller, after processing rows:
$ruleEngine = new RuleEngine();
$ruleEngine->applyRules($newTransactions, company_id());
```

### 7. Update Transaction List
Add category column to transaction list view. Show account name if categorized.

## Deploy & Verify
```bash
docker cp /home/valleybird/projects/akaunting-setup/modules/BankFeeds akaunting:/var/www/html/modules/
docker exec akaunting chown -R www-data:www-data /var/www/html/modules/BankFeeds
docker exec akaunting php artisan view:clear
docker exec akaunting php artisan route:clear
docker exec akaunting php artisan cache:clear
```

## Test OFX Data
Create test file at `/home/valleybird/projects/akaunting-setup/test-data/test-bank.ofx`:
```
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<DTSERVER>20260327
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>0
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456789
<ACCTID>9876543210
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20260301
<DTEND>20260327
<STMTTRN><TRNTYPE>DEBIT<DTPOSTED>20260315<TRNAMT>-1250.00<NAME>CARTER LUMBER #1234</STMTTRN>
<STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260316<TRNAMT>5000.00<NAME>DEPOSIT CHECK 1005</STMTTRN>
<STMTTRN><TRNTYPE>DEBIT<DTPOSTED>20260317<TRNAMT>-327.45<NAME>HOME DEPOT 4567</STMTTRN>
<STMTTRN><TRNTYPE>POS<DTPOSTED>20260318<TRNAMT>-65.00<NAME>SHELL GAS STATION</STMTTRN>
<STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260320<TRNAMT>15000.00<NAME>ACH DEPOSIT ADAM V</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
```

## Success Criteria
- [ ] OFX/QFX files import correctly (dates, amounts, types)
- [ ] Import auto-detects format from file extension
- [ ] OFX skips column mapping step
- [ ] Rules CRUD works (create, edit, delete, reorder)
- [ ] Rules engine categorizes transactions based on matching
- [ ] First-match-wins priority ordering works
- [ ] "Apply All Rules" bulk action works
- [ ] Rules auto-run on new imports
- [ ] Transaction list shows category assignments

## Commit
`feat(bank-feeds): OFX/QFX import and categorization rules engine`
