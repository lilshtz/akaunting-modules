# Audit Report

## Scope

- Audited `modules/` across the 20 target Akaunting modules.
- Excluded `modules/_reference*`.
- Reviewed 630 PHP/Blade files with source-level checks focused on syntax-adjacent defects, tenant scoping, unsafe output, upload validation, secret handling, route/controller integrity, and sensitive endpoint protections.

## Environment Notes

- The workspace does not have a `php` binary available, so `php -l`, PHPUnit, and framework boot validation could not be executed here.
- Verification was performed with repository-wide source scans and targeted manual review of affected files.

## Findings And Fixes

### 1. XSS hardening

- Fixed unsafe raw Blade JSON output in [modules/Pos/Resources/views/orders/index.blade.php](/home/valleybird/projects/akaunting-setup/modules/Pos/Resources/views/orders/index.blade.php).
- Replaced raw `{!! !!}` bootstrapping with `@json(...)` so product/contact data is safely encoded before reaching the POS page script.

### 2. Multi-tenant data isolation fixes

- Fixed unscoped customer/vendor lookups in:
  - [modules/Estimates/Http/Controllers/Estimates.php](/home/valleybird/projects/akaunting-setup/modules/Estimates/Http/Controllers/Estimates.php)
  - [modules/SalesPurchaseOrders/Http/Controllers/SalesOrders.php](/home/valleybird/projects/akaunting-setup/modules/SalesPurchaseOrders/Http/Controllers/SalesOrders.php)
  - [modules/SalesPurchaseOrders/Http/Controllers/PurchaseOrders.php](/home/valleybird/projects/akaunting-setup/modules/SalesPurchaseOrders/Http/Controllers/PurchaseOrders.php)
- Replaced plain `Contact::findOrFail(...)` with company-scoped queries to prevent cross-company contact selection.

- Fixed unscoped parent document lookups in:
  - [modules/CreditDebitNotes/Http/Controllers/CreditNotes.php](/home/valleybird/projects/akaunting-setup/modules/CreditDebitNotes/Http/Controllers/CreditNotes.php)
  - [modules/CreditDebitNotes/Http/Controllers/DebitNotes.php](/home/valleybird/projects/akaunting-setup/modules/CreditDebitNotes/Http/Controllers/DebitNotes.php)
- Parent invoices/bills are now loaded with both `company_id` and the expected document type.

- Fixed unscoped shallow resource access in:
  - [modules/Projects/Http/Controllers/Tasks.php](/home/valleybird/projects/akaunting-setup/modules/Projects/Http/Controllers/Tasks.php)
  - [modules/Projects/Http/Controllers/Milestones.php](/home/valleybird/projects/akaunting-setup/modules/Projects/Http/Controllers/Milestones.php)
  - [modules/Projects/Http/Controllers/Discussions.php](/home/valleybird/projects/akaunting-setup/modules/Projects/Http/Controllers/Discussions.php)
- Added tenant-aware lookup helpers so edit/update/destroy actions can only resolve records whose parent project belongs to the active company.

- Fixed unscoped bank feed reconciliation access in:
  - [modules/BankFeeds/Http/Controllers/Reconciliation.php](/home/valleybird/projects/akaunting-setup/modules/BankFeeds/Http/Controllers/Reconciliation.php)
  - [modules/BankFeeds/Http/Controllers/Matching.php](/home/valleybird/projects/akaunting-setup/modules/BankFeeds/Http/Controllers/Matching.php)
- Bank accounts, feed transactions, and matched Akaunting transactions are now checked against the current company before use.

- Tightened account resolution in [modules/DoubleEntry/Services/AccountBalanceService.php](/home/valleybird/projects/akaunting-setup/modules/DoubleEntry/Services/AccountBalanceService.php).
- When a request company context exists, account lookups are now tenant-scoped before balances are computed.

### 3. Upload validation and file handling

- Hardened employee document uploads in [modules/Employees/Http/Controllers/EmployeeDocuments.php](/home/valleybird/projects/akaunting-setup/modules/Employees/Http/Controllers/EmployeeDocuments.php).
- Added explicit allowed file types in addition to the existing size limit.

- Hardened bank feed column-mapping file access in [modules/BankFeeds/Http/Controllers/Imports.php](/home/valleybird/projects/akaunting-setup/modules/BankFeeds/Http/Controllers/Imports.php).
- Added path-prefix enforcement and file existence checks before reading or deleting uploaded import files.

### 4. Secret handling and credential exposure

- Fixed PayPal settings secret exposure and masked-update handling in:
  - [modules/PaypalSync/Http/Controllers/Settings.php](/home/valleybird/projects/akaunting-setup/modules/PaypalSync/Http/Controllers/Settings.php)
  - [modules/PaypalSync/Resources/views/settings.blade.php](/home/valleybird/projects/akaunting-setup/modules/PaypalSync/Resources/views/settings.blade.php)
- Stored credentials are no longer rendered back into the form in plaintext, and masked placeholders no longer overwrite encrypted values on update.

- Added safer secret accessors in:
  - [modules/PaypalSync/Models/PaypalSyncSettings.php](/home/valleybird/projects/akaunting-setup/modules/PaypalSync/Models/PaypalSyncSettings.php)
  - [modules/Stripe/Models/StripeSettings.php](/home/valleybird/projects/akaunting-setup/modules/Stripe/Models/StripeSettings.php)
- Accessors now tolerate legacy plaintext rows without throwing decryption exceptions.

### 5. Sensitive endpoint protection

- Added rate limiting to the Stripe webhook route in [modules/Stripe/Routes/webhook.php](/home/valleybird/projects/akaunting-setup/modules/Stripe/Routes/webhook.php).
- Fixed company-scoped document loading in [modules/Stripe/Http/Controllers/Webhook.php](/home/valleybird/projects/akaunting-setup/modules/Stripe/Http/Controllers/Webhook.php) so webhook processing cannot attach payments to documents outside the resolved company.

### 6. Company-scoped tax resolution

- Fixed unscoped tax lookups in:
  - [modules/Estimates/Http/Controllers/Estimates.php](/home/valleybird/projects/akaunting-setup/modules/Estimates/Http/Controllers/Estimates.php)
  - [modules/SalesPurchaseOrders/Http/Controllers/SalesOrders.php](/home/valleybird/projects/akaunting-setup/modules/SalesPurchaseOrders/Http/Controllers/SalesOrders.php)
  - [modules/SalesPurchaseOrders/Http/Controllers/PurchaseOrders.php](/home/valleybird/projects/akaunting-setup/modules/SalesPurchaseOrders/Http/Controllers/PurchaseOrders.php)
  - [modules/CreditDebitNotes/Http/Controllers/CreditNotes.php](/home/valleybird/projects/akaunting-setup/modules/CreditDebitNotes/Http/Controllers/CreditNotes.php)
  - [modules/CreditDebitNotes/Http/Controllers/DebitNotes.php](/home/valleybird/projects/akaunting-setup/modules/CreditDebitNotes/Http/Controllers/DebitNotes.php)
- Tax calculations now resolve tax records within the current company instead of globally.

## Verification Summary

- Confirmed there are no remaining raw `{!! !!}` outputs under the audited modules.
- Confirmed the previously identified unscoped `Contact::findOrFail`, `Document::findOrFail`, `Tax::find`, `Account::find`, and `BankFeedTransaction::findOrFail` patterns were removed from the affected modules.
- `php` runtime validation was not possible in this environment.
