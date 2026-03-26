# Task 12: Bank Feeds — Transaction Matching + Reconciliation

## Context
Bank Feeds module has import and categorization (Task 11). Now add matching and reconciliation.

## Objective
Match imported bank transactions to existing Akaunting transactions (invoices, bills, payments). Reconciliation workflow.

## What to Build

### 1. Auto-Matching Service
`Services/TransactionMatcher.php`:
- Match by: amount + date range (±3 days) + vendor name similarity
- Confidence scoring: exact amount match = high, date match = medium, vendor match = bonus
- Suggest top 3 matches for each imported transaction
- Auto-match if confidence > threshold (configurable)

### 2. Matching UI
- Review page: imported transaction on left, suggested matches on right
- Accept match → links bank_feed_transaction to Akaunting transaction, marks reconciled
- Reject match → keep as unmatched
- Create new → create bill/payment from imported transaction
- Ignore → mark as non-accounting (ATM fees, personal)
- Bulk actions: match all high-confidence, ignore selected

### 3. Reconciliation Workflow
- Select bank account and statement period
- Show: opening balance, imported transactions, closing balance
- Match/unmatch transactions
- Reconciliation difference (should be $0 when complete)
- Mark period as reconciled
- Reconciliation history

### 4. Duplicate Detection
- Flag imported transactions that match previously imported ones (same date + amount + description)

### 5. Dashboard Widget
- Unmatched transactions count
- Unreconciled accounts count

## Verification
1. Import CSV with 10 transactions → 5 auto-match to existing invoices/bills
2. Manually match 3 more via suggestion UI
3. Create new bill from 1 unmatched transaction
4. Ignore 1 transaction
5. Reconciliation page shows $0 difference when all matched
6. Mark period as reconciled

## Commit Message
`feat(modules): bank feed transaction matching and reconciliation workflow`
