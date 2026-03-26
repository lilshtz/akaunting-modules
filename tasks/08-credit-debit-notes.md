# Task 08: Credit & Debit Notes Module

## Context
Depends on DoubleEntry for journal posting. Uses Document model like Estimates.

## Objective
Issue credit notes to customers (refunds/adjustments) and debit notes to vendors (returns). Apply credits to future invoices.

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/CreditDebitNotes/`

### 2. Database
- Use core documents table with type='credit-note' and type='debit-note'
- **Table: `credit_note_applications`** — id, credit_note_id, invoice_id, amount, date, created_at (tracks credit applied to invoices)
- **Table: `credit_debit_note_settings`** — company_id, cn_prefix, cn_next_number, dn_prefix, dn_next_number

### 3. Controllers
- `CreditNotes` — CRUD for credit notes (issue to customer, link to original invoice)
  - Apply credit as payment on another invoice
  - Make refund (record cash refund)
  - Convert credit note → new invoice
  - Email/share public link
  - PDF generation
- `DebitNotes` — CRUD for debit notes (issue to vendor, link to original bill)
  - Convert debit note → new bill
  - Email/share public link
  - PDF generation

### 4. Features
- Line items with description, qty, price, tax, discount
- Statuses: Draft → Sent → Open → Partial → Closed → Cancelled
- Link to original invoice/bill (required)
- Auto-journal: Credit note → DR Revenue/AR, CR Customer Credit
- Auto-journal: Debit note → DR Vendor Credit, CR Expense/AP
- Customizable templates matching invoice/bill branding
- Notes field with reasons for issuance
- Reports: outstanding credits by customer, issued notes by period

### 5. Portal
Customer can view credit notes via public link.

### 6. Sidebar
Add "Credit Notes" and "Debit Notes" under Sales and Purchases respectively.

## Verification
1. Issue credit note linked to invoice — journal entry created
2. Apply credit to another invoice — reduces amount due
3. Issue debit note linked to bill — journal entry created
4. Convert credit note to invoice — new invoice created
5. PDF renders correctly
6. Outstanding credits report shows correct balances

## Commit Message
`feat(modules): credit and debit notes with refund tracking and credit application`
