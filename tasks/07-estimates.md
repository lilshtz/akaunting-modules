# Task 07: Estimates Module

## Context
Standalone document module. Uses Akaunting core Document model with type='estimate'.

## Objective
Build Estimates module — create quotes, send to customers, get approval, convert to invoices.

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/Estimates/` with standard structure.

### 2. Database Migration
**Table: `estimate_settings`** — company_id (unique), prefix (varchar default 'EST-'), next_number (int default 1), default_terms (text nullable), template (varchar default 'default'), approval_required (boolean default true)

Estimates use core `documents` table with `type = 'estimate'`. No separate documents table needed.

### 3. Controller: Estimates
Full CRUD using Akaunting's Document model:
- index: list estimates with status badges (Draft/Sent/Viewed/Approved/Refused/Converted/Expired)
- create: form with customer selector, line items (description, qty, price, tax, discount), expiry date, notes, terms, file attachments
- store: validate, generate estimate number from settings, save as Document type=estimate
- show: detail view with timeline (sent, viewed, approved/refused dates)
- edit/update: only Draft and Refused can be edited
- destroy: only Draft can be deleted
- send: email estimate PDF to customer with approve/refuse links
- approve: mark as approved (from customer portal link or manual)
- refuse: mark as refused with reason
- convert: create Invoice from estimate (copy all line items, link back)
- duplicate: copy estimate as new draft
- PDF generation with company branding

### 4. Customer Portal
- Public URL per estimate (token-based, no login required)
- Customer views estimate details
- Approve button: marks estimate as approved, emails notification to company
- Refuse button: optional reason textarea, marks refused, emails notification
- View tracking: record when customer first views

### 5. Notifications
- Email to customer when estimate sent
- Email to company when customer views/approves/refuses
- In-app notification for status changes

### 6. Routes
Admin routes for CRUD + actions. Portal routes for customer view/approve/refuse.

### 7. Sidebar
Add "Estimates" under Sales section.

### 8. Reports
Estimate summary: total sent, approved rate, average value, conversion rate to invoice.

## Verification
1. Create estimate with 3 line items, set expiry date — saves as draft
2. Send to customer — email sent with PDF, status changes to Sent
3. Customer opens portal link — status changes to Viewed
4. Customer clicks Approve — status changes to Approved, company notified
5. Convert approved estimate to invoice — invoice created with same line items
6. Expired estimate (past expiry date) shows as Expired
7. PDF renders with company logo and branding

## Commit Message
`feat(modules): estimates with approval workflow, portal, and invoice conversion`
