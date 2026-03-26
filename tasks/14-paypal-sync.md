# Task 14: PayPal Sync Module

## Context
Import PayPal transactions into Akaunting banking.

## Objective
Connect PayPal account, import transactions, match to invoices.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/PaypalSync/`

### 2. Database
- `paypal_sync_settings` — company_id, client_id (encrypted), client_secret (encrypted), mode (sandbox/live), bank_account_id (FK — which Akaunting bank account to import into), last_sync (datetime)
- `paypal_sync_transactions` — id, paypal_transaction_id (unique), company_id, bank_transaction_id (FK nullable), amount, currency, date, description, payer_email, status, raw_json, created_at

### 3. Features
- Settings: enter PayPal API credentials, select target bank account, toggle sandbox
- Sync button: fetch transactions from PayPal API (Transactions Search API v1)
- Auto-create Akaunting bank transactions from PayPal transactions
- Match PayPal payments to invoices by amount + customer email
- Sync history log
- Scheduled sync (daily cron via Artisan command)

## Verification
1. Configure PayPal sandbox credentials
2. Click sync → transactions imported
3. Imported transactions appear in bank account
4. Matching suggestions for invoice payments

## Commit Message
`feat(modules): paypal sync for transaction import and matching`
