# PRD: Build All Paid Akaunting Modules — Free, Self-Hosted

## Problem
Akaunting is already running self-hosted at `http://localhost:8085` (Docker, Laravel 10, MariaDB). The free tier includes core invoicing, bills, banking, dashboard, settings, and two modules (OfflinePayments, PaypalStandard). All the valuable features — double-entry accounting, estimates, inventory, payroll, CRM, projects, receipts, budgeting, expense claims, custom fields, and more — are locked behind paid modules ($30-80 each, or $300-500+ total).

**We build them ourselves as native Akaunting Laravel modules.** Same UI. Same DB. Same module system. Free forever.

## Goal
Build and install every paid Akaunting module as custom Laravel modules on the existing self-hosted instance. When done, Andrew's Akaunting should have **full feature parity with Akaunting's Ultimate Cloud plan** — zero paid licenses.

## Architecture

### How Akaunting Modules Work
- Modules live in `/var/www/html/modules/{ModuleName}/`
- Each is a Laravel package: Controllers, Models, Migrations, Views, Routes, Providers
- Registered via `module.json` (alias, version, providers, requires, reports, widgets)
- Installed via `php artisan module:install {alias} {company_id}`
- Created via `php artisan module:make {alias}`
- Views use Blade templates with Akaunting's existing Tailwind component system
- Routes: `admin.php` (backend) and `portal.php` (client portal)
- DB migrations run per-module in `Database/Migrations/`
- Events/Listeners hook into core Akaunting events (sidebar menu, document creation, etc.)

### Existing Core Models (DO NOT recreate)
These already exist in the Akaunting core and our modules extend them:
- `Company`, `User`, `UserCompany`, `Role`, `Permission`
- `Account` (bank), `Transaction`, `Reconciliation`, `Transfer`
- `Contact` (customers + vendors), `ContactPerson`
- `Document` (invoices + bills), `DocumentItem`, `DocumentItemTax`, `DocumentHistory`, `DocumentTotal`
- `Item`, `ItemTax`, `Category`, `Currency`, `Tax`
- `Setting`, `EmailTemplate`, `Recurring`, `Report`, `Widget`, `Dashboard`
- `Media`, `Notification`, `Module`, `ModuleHistory`

### Tech Environment
- **Akaunting:** v3.x (Laravel 10.50.0)
- **PHP:** 8.1+
- **DB:** MariaDB 11 (Docker: `akaunting-db`)
- **Container:** `akaunting` → port 8085
- **Module path:** `/var/www/html/modules/`

---

## Modules to Build (20 total)

### Module 1: DoubleEntry ⭐ CRITICAL
**The backbone. Everything else depends on this.**

Chart of Accounts, General Ledger, Balance Sheet, Trial Balance, P&L, Manual Journals.

**Features:**
- Chart of Accounts (COA)
  - Account types: Asset, Liability, Equity, Income, Expense
  - Sub-accounts with parent/child hierarchy (unlimited depth)
  - Account codes (1xxx-5xxx numbering convention)
  - Enable/disable individual accounts
  - Default system accounts (AR, AP, Retained Earnings, etc.)
  - Import from CSV (QuickBooks export compatible)
  - Opening balances per account
- General Ledger
  - Every invoice, bill, payment, transfer auto-posts journal entries (debit + credit)
  - Filter by: account, date range, contact, accounting basis
  - Running balance per account
  - Export CSV/PDF
- Manual Journal Entries
  - Multi-line entries with debit/credit per line
  - Validation: total debits must equal total credits
  - Attach supporting documents
  - Recurring journals (monthly accruals, depreciation)
  - Reference number, date, description, currency
- Trial Balance
  - Debit/credit columns per account
  - Date range filter
  - Verify books balance before period close
- Balance Sheet
  - Assets / Liabilities / Equity sections
  - Hierarchical (follows COA structure)
  - As-of-date selector
  - Comparative periods (this year vs last year)
- Profit & Loss (Income Statement)
  - Income vs Expenses with net profit/loss
  - Monthly / Quarterly / Annual breakdown
  - Category-level detail
  - Comparative periods
  - Cash basis and accrual basis toggle
- Double-Entry Dashboard widget
  - Quick COA summary, recent journal entries, account balances

**DB Tables:**
- `double_entry_accounts` — id, company_id, parent_id, code, name, type, description, opening_balance, enabled
- `double_entry_journals` — id, company_id, date, reference, description, status, basis
- `double_entry_journal_lines` — id, journal_id, account_id, debit, credit, description
- `double_entry_account_defaults` — company_id, type, account_id (maps "accounts_receivable" → account #1200)

**Requires:** None (foundation module)
**Commit:** `feat(modules): double-entry accounting with COA, ledger, journals, reports`

---

### Module 2: Estimates ⭐
**Create quotes, get approval, convert to invoices.**

**Features:**
- Estimate CRUD with line items (description, quantity, price, tax, discount)
- Auto-numbering with customizable prefix per company (EST-0001)
- Statuses: Draft → Sent → Viewed → Approved → Refused → Converted → Expired
- Expiration date with auto-expire
- Customer approval: email link with Approve/Refuse buttons + optional notes
- Notification on view/approve/refuse (email + in-app)
- One-click convert Estimate → Invoice (preserves all line items)
- Estimate → Sales Order conversion (if Sales/Purchase Orders module installed)
- PDF generation with company-branded template
- Email estimate as PDF attachment
- Print / Download
- Attach files (drawings, specs, scope documents)
- Notes and terms/conditions fields
- Recurring estimates
- Portal view (customer sees via link)
- Estimate reports: sent, approved, refused, converted rates

**DB Tables:**
- Uses core `documents` table with `type = 'estimate'`
- `estimate_settings` — company_id, prefix, next_number, default_terms, template

**Requires:** None
**Commit:** `feat(modules): estimates with approval workflow and invoice conversion`

---

### Module 3: CustomFields
**Add custom data fields to any record type.**

**Features:**
- Define custom fields for: invoices, bills, estimates, customers, vendors, items, accounts, employees, transfers, projects, expenses
- Field types: Text, Textarea, Number, Date, DateTime, Time, Select (dropdown), Checkbox, Toggle, URL, Email
- Required vs optional validation
- Default values
- Position ordering (field display order on forms)
- Custom field size (full width, half width)
- Show/hide on PDF exports
- Integration with all other modules (fields render on create/edit forms automatically)
- Filter and search by custom field values
- Bulk import/export with custom field data
- Unlimited custom fields per entity type

**DB Tables:**
- `custom_field_definitions` — id, company_id, entity_type, name, field_type, required, default_value, options_json, position, show_on_pdf, width
- `custom_field_values` — id, definition_id, entity_type, entity_id, value

**Requires:** None
**Commit:** `feat(modules): custom fields for all record types`

---

### Module 4: Receipts
**OCR receipt scanning, auto-capture, and transaction creation.**

**Features:**
- Upload receipt images (web UI drag-drop, API upload for Bookie)
- OCR extraction: vendor/merchant name, date, total amount, tax amount, currency
- OCR providers: Tesseract.js (free/local) + optional Taggun/Mindee API keys
- Review extracted data before saving (edit any field)
- Auto-match to existing vendor
- Auto-categorize by vendor rules
- Create bill or payment directly from receipt
- Attach receipt image to resulting transaction
- Duplicate detection (same vendor + amount + date within 3 days)
- Receipt inbox: queue of uploaded but unprocessed receipts
- Bulk upload and processing
- Receipt search: by vendor, date range, amount range, status
- Statuses: Uploaded → Reviewed → Processed → Matched
- Multi-language receipt support (52 languages via OCR)
- Receipt reports

**DB Tables:**
- `receipts` — id, company_id, image_path, ocr_raw_json, vendor_name, date, amount, tax_amount, currency, category_id, status, transaction_id, created_at

**Requires:** None (enhanced by DoubleEntry for journal posting)
**Commit:** `feat(modules): receipt management with OCR and auto-categorization`

---

### Module 5: Employees ⭐
**Employee/contractor management — HR foundation.**

**Features:**
- Employee CRUD: name, email, phone, address, photo, hire date, birthday
- Departments: create/manage departments, assign employees
- Salary information: amount, type (hourly/weekly/monthly/yearly), currency
- Bank details for payment
- Employee types: Full-time, Part-time, Contractor, Seasonal
- 1099 vs W-2 classification
- Login access: give employees portal access with role-based permissions
- Document storage per employee (W-9, insurance cert, license, agreements)
- Employee directory with search/filter
- Active/Inactive/Terminated status
- Employment history (dates, role changes)
- Employee dashboard: headcount, department breakdown

**DB Tables:**
- `employees` — id, company_id, contact_id, department_id, salary, salary_type, bank_details_json, hire_date, birthday, type, classification, status
- `departments` — id, company_id, name, description, manager_id

**Requires:** None (Akaunting core Contact model used as base)
**Commit:** `feat(modules): employee and contractor management with departments`

---

### Module 6: Payroll
**Pay calendars, payroll runs, payslips, benefits, deductions.**

**Features:**
- Pay calendars: weekly, bi-weekly, monthly, custom frequency
- Pay calendar scheduling: auto-calculates next run date
- Payroll run workflow: Create → Review → Approve → Process → Complete
- Per-employee payslip with: gross pay, itemized benefits, itemized deductions, net pay
- Pay Items — Benefits: Bonus, Commission, Allowance, Benefit, Expense Reimbursement (customizable list)
- Pay Items — Deductions: Tax, Insurance, Loan, Advance Pay, Miscellaneous (customizable list)
- Auto-calculate totals (gross + benefits - deductions = net)
- PDF payslip generation (print-ready)
- Email payslips to employees
- Auto-post payroll to journal entries (salary expense → cash/bank)
- Payroll history per employee
- Payroll summary reports: by period, by department, by employee
- Multi-company payroll (each company runs independently)
- Attachment support on payslips
- Notifications: payment due, payroll processed

**DB Tables:**
- `pay_calendars` — id, company_id, name, frequency, start_date, next_run_date
- `pay_items` — id, company_id, type (benefit/deduction), name, default_amount, is_percentage
- `payroll_runs` — id, pay_calendar_id, period_start, period_end, status, total_gross, total_net, approved_by
- `payslips` — id, payroll_run_id, employee_id, gross, benefits_json, deductions_json, net, pdf_path
- `payslip_items` — id, payslip_id, pay_item_id, amount

**Requires:** Employees module
**Commit:** `feat(modules): payroll with pay calendars, payslips, benefits/deductions`

---

### Module 7: Projects
**Project management with tasks, milestones, time tracking, and job costing.**

**Features:**
- Project CRUD: name, client (contact), description, status, budget, start/end dates
- Billing styles: Project hours, Task hours, Fixed rate
- Project statuses: Active, Completed, On Hold, Cancelled
- Milestones with target dates and completion percentage
- Tasks within milestones: name, description, assignee, priority (Low/Medium/High/Critical), status (To Do/In Progress/Review/Done)
- Time tracking: start/stop timer, manual timesheet entry
- Timesheets auto-generated from task activity
- Link invoices and bills to projects
- Project P&L: total income vs total costs
- Budget vs actual tracking with variance calculation and alerts
- Project dashboard: progress %, budget burn rate, timeline, activity feed
- Team member assignment
- Discussion threads per project
- Project cash flow statement
- Activity timeline (log of all changes)
- Task priority sorting and filtering
- Project reports: profitability, time spent, budget variance

**DB Tables:**
- `projects` — id, company_id, contact_id, name, description, status, billing_type, billing_rate, budget, start_date, end_date
- `project_milestones` — id, project_id, name, description, target_date, completed_at
- `project_tasks` — id, milestone_id, name, description, assignee_id, priority, status, estimated_hours
- `project_timesheets` — id, task_id, user_id, started_at, ended_at, hours, billable
- `project_transactions` — id, project_id, document_type, document_id (links invoices/bills to project)
- `project_discussions` — id, project_id, user_id, body, created_at

**Requires:** Employees (for assignees), DoubleEntry (for P&L)
**Commit:** `feat(modules): project management with milestones, time tracking, job costing`

---

### Module 8: ExpenseClaims
**Employee expense submission, approval, and reimbursement.**

**Features:**
- Expense claim CRUD: employee, date range, description, total
- Claim line items: date, category, description, amount, receipt attachment
- Claim statuses: Draft → Submitted → Pending Approval → Approved → Refused → Paid
- Approval workflow: define approver per claim (or default approver)
- Mark items as "Paid by Employee" for reimbursement tracking
- Approver can add notes/reasons for refusal
- Auto-update Chart of Accounts on approval (expense → payable)
- Reimbursement tracking (record payment to employee)
- Claim categories: Materials, Travel, Tools, Equipment, Meals, Misc (customizable)
- PDF export of claims with receipts
- Due dates on claims
- Import/export claims
- Reports: by employee, by category, by period, pending vs approved
- Notification: new claim submitted, approved, refused, paid

**DB Tables:**
- `expense_claims` — id, company_id, employee_id, approver_id, status, description, total, due_date, approved_at, refused_at, refusal_reason, paid_at
- `expense_claim_items` — id, claim_id, category_id, date, description, amount, receipt_path

**Requires:** Employees
**Commit:** `feat(modules): expense claims with approval workflow and reimbursement`

---

### Module 9: CRM
**Contact management, leads, deals pipeline, activity tracking.**

**Features:**
- CRM Contacts: name, email, phone, company, source (web/referral/email/cold), stage, owner
- Companies: name, address, contacts list, default currency and stage
- Deals/Opportunities: name, contact, value, stage, expected close date, status (Open/Won/Lost/Deleted)
- Pipeline stages: Lead → Qualified → Proposal → Negotiation → Won → Lost (fully customizable)
- Drag-and-drop pipeline board view
- Activity logging: calls, meetings, emails, notes, tasks — each with date/time
- Schedule calls and meetings
- Create tasks for contacts
- Send custom emails to contacts
- Connect invoices to deals (auto-sync with Akaunting customer data)
- Contact creation auto-syncs to Akaunting customers
- CRM reports: activity, growth (leads/opportunities/subscribers per period), pipeline conversion rates
- Activity timeline per contact and per deal
- Search and filter contacts by any field

**DB Tables:**
- `crm_contacts` — id, company_id, name, email, phone, crm_company_id, source, stage, owner_user_id, notes
- `crm_companies` — id, company_id, name, address, currency, default_stage
- `crm_deals` — id, company_id, crm_contact_id, name, value, stage, expected_close, status, invoice_id
- `crm_pipeline_stages` — id, company_id, name, position, color
- `crm_activities` — id, company_id, crm_contact_id, crm_deal_id, type (call/meeting/email/note/task), description, scheduled_at, completed_at

**Requires:** None
**Commit:** `feat(modules): CRM with contacts, deals, pipeline, activity tracking`

---

### Module 10: CreditDebitNotes
**Credit notes (customer refunds) and debit notes (vendor returns).**

**Features:**
- Credit notes: issue to customer → reduces AR balance
- Debit notes: issue to vendor → reduces AP balance
- Link to original invoice/bill
- Credit note line items with description, quantity, price, tax, discount
- Statuses: Draft → Sent → Open → Partial → Closed → Cancelled
- Apply credit to future invoices (use as payment method)
- Refund processing (record cash refund)
- Convert credit note → invoice, debit note → bill
- Email credit/debit notes to customer/vendor (PDF attachment)
- Public link for customer viewing
- Customizable templates matching invoice branding
- Auto-update account balances and journal entries
- Add taxes and discounts
- Print / Download / Export
- Notes with reasons for issuance
- Reports: outstanding credits, issued notes by period

**DB Tables:**
- Uses core `documents` table with `type = 'credit-note'` and `type = 'debit-note'`
- `credit_note_applications` — id, credit_note_id, invoice_id, amount, date (tracks credit applied to invoices)

**Requires:** DoubleEntry (for journal posting)
**Commit:** `feat(modules): credit and debit notes with refund tracking`

---

### Module 11: Inventory
**Stock management, warehouses, variants, barcodes, adjustments, transfers.**

**Features:**
- Item stock tracking: quantity on hand per warehouse
- Item variants: size, color, type attributes with unique SKU per variant
- Item groups: group related items
- Warehouses: name, address, email, phone, postal code, country
- Barcode generation: auto-generate and print barcodes per item/variant
- Default barcode format selection
- Stock adjustments: add/remove stock for damaged, missing, stolen, returned items
- Adjustment reasons (customizable list)
- Transfer orders: move stock between warehouses
- Transfer statuses: Draft → In Transit → Received → Cancelled
- Low stock alerts (configurable threshold per item)
- Auto-update stock from invoice (sold) and bill (purchased) transactions
- Inventory reports: stock status, stock value, purchase/sales summary, per-item income/expense/quantity by warehouse
- Inventory history log (all stock movements)
- Unit of measurement per item

**DB Tables:**
- `inventory_warehouses` — id, company_id, name, address, email, phone, enabled
- `inventory_stock` — id, item_id, warehouse_id, quantity
- `inventory_variants` — id, item_id, name, sku, attributes_json
- `inventory_adjustments` — id, company_id, warehouse_id, item_id, quantity, reason, date, description
- `inventory_transfer_orders` — id, company_id, from_warehouse_id, to_warehouse_id, status, date, description
- `inventory_transfer_items` — id, transfer_order_id, item_id, quantity
- `inventory_item_groups` — id, company_id, name, description
- `inventory_item_group_items` — item_group_id, item_id

**Requires:** None (enhanced by DoubleEntry for COGS tracking)
**Commit:** `feat(modules): inventory with warehouses, variants, stock tracking, barcodes`

---

### Module 12: Budgets
**Budget planning, forecasting, and variance tracking.**

**Features:**
- Create budgets per company, per period (monthly/quarterly/annual)
- Budget by COA account (map planned amounts to each income/expense account)
- Budget vs actual comparison reports (actual pulled from journal entries)
- Variance calculation: absolute and percentage
- Over-budget alerts and notifications
- Income and expense forecasting (extrapolate trends)
- Multiple budget scenarios (optimistic/realistic/pessimistic)
- Copy budget from previous period
- Budget dashboard widget
- Visual charts: planned vs actual bar/line charts
- Export budget reports

**DB Tables:**
- `budgets` — id, company_id, name, period_start, period_end, status (draft/active/closed)
- `budget_lines` — id, budget_id, account_id, amount

**Requires:** DoubleEntry (COA accounts for budget lines, journals for actuals)
**Commit:** `feat(modules): budgeting with variance tracking and forecasting`

---

### Module 13: SalesPurchaseOrders
**Sales orders and purchase orders with conversion to invoices/bills.**

**Features:**
- Sales Order CRUD: customer, line items, quantities, prices, taxes, discounts, delivery date
- Purchase Order CRUD: vendor, line items, quantities, prices, taxes, delivery date
- Order statuses: Draft → Sent → Confirmed → Issued → Cancelled
- Convert Sales Order → Invoice (one click)
- Convert Purchase Order → Bill (one click)
- Convert Estimate → Sales Order (integration with Estimates module)
- Convert Sales Order → Purchase Order
- Email orders as PDF to customer/vendor
- Order templates (customizable per company)
- Order numbering: auto-increment with prefix (SO-0001, PO-0001)
- Attach receipts, bills, or supporting files
- Track customer/vendor transactions per order
- Add taxes and discounts
- Reports: sales history, purchase history, by customer, by vendor
- Import/Export orders
- Print / Download

**DB Tables:**
- Uses core `documents` table with `type = 'sales-order'` and `type = 'purchase-order'`
- `order_settings` — company_id, so_prefix, so_next_number, po_prefix, po_next_number

**Requires:** None (enhanced by Estimates, Inventory)
**Commit:** `feat(modules): sales and purchase orders with invoice/bill conversion`

---

### Module 14: Roles
**Roles & permissions for granular access control.**

**Features:**
- Create custom roles beyond the default Admin/User
- Predefined roles: Manager (full access), Accountant (financial access), Employee (limited), Customer (portal only)
- Granular permissions per role: View, Create, Edit, Delete for each module/feature
- Assign roles to users per company
- Restrict access to specific apps/modules
- Permission levels for: dashboard, reports, purchases, sales, banking, settings, installed apps
- Employee role: access to expense claims, time tracking, personal payslips
- Accountant role: access to journals, ledger, reports, reconciliation (no destructive actions)
- Customer role: client portal access, view/pay invoices, view proposals

**DB Tables:**
- Extends core `roles` and `permissions` tables
- `role_module_permissions` — role_id, module_alias, can_view, can_create, can_edit, can_delete

**Requires:** None
**Commit:** `feat(modules): roles and permissions with granular access control`

---

### Module 15: BankFeeds
**Auto-import bank transactions via CSV/OFX/Plaid.**

**Features:**
- CSV import: upload bank statement CSV, map columns to fields (date, description, amount, type)
- OFX/QFX import: parse standard bank export files
- Plaid integration (optional): connect bank accounts for auto-sync via API key
- GoCardless integration (optional): European bank connections
- Auto-categorization: rules engine (if vendor contains "Home Depot" → category "Materials")
- Categorization rules: vendor name match, amount range, description keywords
- Transaction matching: auto-match imported transactions to existing invoices/bills/payments
- Reconciliation workflow: review imported transactions, match, categorize, approve
- Import up to 12 months historical data
- Dashboard: cash flow, income/expense from bank data
- Duplicate detection (same date + amount + description)
- Multi-bank support per company
- Secure: data stays on your server

**DB Tables:**
- `bank_feed_connections` — id, company_id, bank_account_id, provider (csv/plaid/gocardless), credentials_json, last_sync
- `bank_feed_imports` — id, connection_id, imported_at, row_count, status
- `bank_feed_rules` — id, company_id, field (vendor/description/amount), operator (contains/equals/gt/lt), value, category_id, vendor_id

**Requires:** None (enhanced by DoubleEntry for journal posting)
**Commit:** `feat(modules): bank feeds with CSV/OFX import and auto-categorization`

---

### Module 16: POS (Point of Sale)
**In-app POS for tracking walk-in sales.**

**Features:**
- POS interface: product grid or list, add items to order basket
- Quantity, price, discount per item in basket
- Barcode scanner support (scan items into basket)
- Multiple tabs: serve multiple customers simultaneously with timestamps
- Customer selection: import from existing contacts
- Payment methods: cash, card, multiple methods per order
- Receipt generation: print, download, email to customer
- Configurable receipt paper size
- Order history: list processed POS orders with date, customer, amount, status
- Order statuses: Completed, Refunded, Cancelled
- Refund processing
- Bill splitting
- Daily sales summary
- Auto-create invoice from POS sale
- Integrates with Inventory (auto-deduct stock)

**DB Tables:**
- `pos_orders` — id, company_id, contact_id, status, subtotal, tax, discount, total, payment_method, created_at
- `pos_order_items` — id, order_id, item_id, name, quantity, price, discount, total
- `pos_settings` — company_id, receipt_width, default_payment_method, auto_create_invoice

**Requires:** None (enhanced by Inventory)
**Commit:** `feat(modules): point of sale with barcode scanning and receipts`

---

### Module 17: Appointments
**Schedule appointments, manage leave, accept payments.**

**Features:**
- Appointment forms: customizable fields, send to customers/employees/contacts
- Appointment scheduling: date, time, duration, location, assigned user
- Employee leave management: request, approve, track leave days
- Accept payments through appointment forms
- Calendar view of appointments
- Appointment reminders (email)
- Assign multiple users to manage forms
- Appointment reports: history, upcoming, by user
- Customer self-scheduling via public link

**DB Tables:**
- `appointments` — id, company_id, contact_id, user_id, date, start_time, end_time, location, status, notes
- `appointment_forms` — id, company_id, name, fields_json, public_link, enabled
- `leave_requests` — id, company_id, employee_id, approver_id, type (vacation/sick/personal), start_date, end_date, status, reason

**Requires:** Employees
**Commit:** `feat(modules): appointments and leave management`

---

### Module 18: Stripe
**Accept invoice payments via Stripe.**

**Features:**
- Stripe payment gateway on client portal invoices
- Customer clicks "Pay" → redirected to Stripe checkout
- Webhooks: auto-record payment on successful charge
- Auto-sync payment to invoice (mark paid/partial)
- Supports: credit card, debit card, ACH (US), other Stripe methods
- Stripe API key configuration in settings
- Payment history and reconciliation
- Refund via Stripe
- Test mode support

**DB Tables:**
- `stripe_settings` — company_id, api_key_encrypted, webhook_secret, test_mode
- `stripe_payments` — id, company_id, document_id, stripe_charge_id, amount, status, created_at

**Requires:** None
**Commit:** `feat(modules): stripe payment gateway for invoices`

---

### Module 19: PayPalSync
**Sync PayPal transactions into Akaunting.**

**Features:**
- Connect PayPal account via API credentials
- Auto-import PayPal transactions as banking transactions
- Match PayPal payments to invoices
- Two-way sync: record payment in Akaunting → marked in PayPal context
- PayPal balance tracking
- Transaction categorization

**DB Tables:**
- `paypal_sync_settings` — company_id, client_id_encrypted, client_secret_encrypted, mode, last_sync
- `paypal_sync_transactions` — id, company_id, paypal_transaction_id, bank_transaction_id, amount, date, status

**Requires:** None
**Commit:** `feat(modules): paypal sync for transaction import`

---

### Module 20: AutoScheduleReports
**Auto-generate and email/post reports on a schedule.**

**Features:**
- Schedule any built-in report (P&L, Balance Sheet, Trial Balance, Cash Flow, AR/AP Aging, etc.)
- Frequency: daily, weekly, monthly, quarterly, annually
- Email reports as PDF to specified recipients
- Webhook/API delivery (post to Discord via Bookie)
- Report format: PDF, CSV, Excel
- Custom date ranges (previous month, previous quarter, YTD, custom)
- Multi-report scheduling (run several reports at once)
- Schedule management UI: list, edit, enable/disable, delete schedules
- Execution log: history of generated reports with download links

**DB Tables:**
- `report_schedules` — id, company_id, report_type, frequency, next_run, recipients_json, format, date_range_type, enabled
- `report_schedule_runs` — id, schedule_id, ran_at, file_path, status

**Requires:** DoubleEntry (for financial reports)
**Commit:** `feat(modules): auto-schedule reports with email delivery`

---

## Build Order (Codex Task Sequence)

Each task is one Codex session — fresh context, atomic, completable independently.

**Phase 1 — Foundation Modules (Tasks 01-06)**
```
tasks/01-scaffold-double-entry.md        — Module scaffold + COA + account types
tasks/02-journal-engine.md               — Journal entries + auto-posting from invoices/bills
tasks/03-ledger-reports.md               — General Ledger + Trial Balance views
tasks/04-balance-sheet-pnl.md            — Balance Sheet + P&L report pages
tasks/05-custom-fields.md                — Custom fields module (all entity types)
tasks/06-employees-departments.md        — Employees + departments module
```

**Phase 2 — Sales & Documents (Tasks 07-10)**
```
tasks/07-estimates.md                    — Estimates with approval + convert to invoice
tasks/08-credit-debit-notes.md           — Credit/debit notes linked to invoices/bills
tasks/09-sales-purchase-orders.md        — SO/PO with conversion to invoice/bill
tasks/10-receipts-ocr.md                 — Receipt upload + OCR + transaction creation
```

**Phase 3 — Banking & Payments (Tasks 11-14)**
```
tasks/11-bank-feeds-csv.md               — CSV/OFX import + categorization rules
tasks/12-bank-feeds-matching.md          — Transaction matching + reconciliation
tasks/13-stripe-gateway.md               — Stripe payment gateway
tasks/14-paypal-sync.md                  — PayPal transaction sync
```

**Phase 4 — Projects & HR (Tasks 15-19)**
```
tasks/15-projects-milestones.md          — Projects + milestones + tasks
tasks/16-projects-time-budget.md         — Time tracking + budget vs actual
tasks/17-expense-claims.md               — Expense claims with approval
tasks/18-payroll-calendars.md            — Pay calendars + payroll runs
tasks/19-payroll-payslips.md             — Payslip generation + benefits/deductions
```

**Phase 5 — CRM & Inventory (Tasks 20-24)**
```
tasks/20-crm-contacts-companies.md       — CRM contacts + companies
tasks/21-crm-deals-pipeline.md           — Deals + pipeline + activities
tasks/22-inventory-warehouses.md         — Inventory items + warehouses + stock
tasks/23-inventory-variants-barcodes.md  — Variants + barcodes + adjustments + transfers
tasks/24-budgets.md                      — Budget planning + variance reports
```

**Phase 6 — Operations & Polish (Tasks 25-28)**
```
tasks/25-roles-permissions.md            — Roles & granular permissions
tasks/26-pos.md                          — Point of Sale
tasks/27-appointments-leave.md           — Appointments + leave management
tasks/28-auto-schedule-reports.md        — Scheduled report generation + delivery
```

---

## Acceptance Criteria (Summary)

| # | Module | Key Test | Commit |
|---|--------|----------|--------|
| AC1 | DoubleEntry | COA populated, journal entry creates balanced debit/credit, P&L generates | `feat: double-entry` |
| AC2 | Estimates | Create estimate, customer approves via link, converts to invoice | `feat: estimates` |
| AC3 | CustomFields | Add text/date/select fields to invoices, fields render on form + PDF | `feat: custom-fields` |
| AC4 | Receipts | Upload image, OCR extracts vendor/amount/date, creates bill | `feat: receipts` |
| AC5 | Employees | Add employee with department and salary, shows in directory | `feat: employees` |
| AC6 | Payroll | Run payroll, generate PDF payslip, posts journal entry | `feat: payroll` |
| AC7 | Projects | Create project, link invoice, budget vs actual shows variance | `feat: projects` |
| AC8 | ExpenseClaims | Submit claim, approver approves, marked for reimbursement | `feat: expense-claims` |
| AC9 | CRM | Add contact, create deal, move through pipeline stages | `feat: crm` |
| AC10 | CreditDebitNotes | Issue credit note, apply as payment to future invoice | `feat: credit-debit-notes` |
| AC11 | Inventory | Add item to warehouse, stock decreases on invoice | `feat: inventory` |
| AC12 | Budgets | Create budget, view variance report against actuals | `feat: budgets` |
| AC13 | SalesPurchaseOrders | Create SO, convert to invoice; create PO, convert to bill | `feat: sales-purchase-orders` |
| AC14 | Roles | Create custom role, user can only access permitted modules | `feat: roles` |
| AC15 | BankFeeds | Import CSV, auto-categorize, match to existing transactions | `feat: bank-feeds` |
| AC16 | POS | Add items to basket, process sale, print receipt | `feat: pos` |
| AC17 | Appointments | Schedule appointment, employee requests leave | `feat: appointments` |
| AC18 | Stripe | Customer pays invoice via Stripe, payment auto-recorded | `feat: stripe` |
| AC19 | PayPalSync | PayPal transactions imported and matched | `feat: paypal-sync` |
| AC20 | AutoScheduleReports | Schedule monthly P&L, auto-generates and emails PDF | `feat: auto-schedule-reports` |

---

## Dependencies
- Akaunting Docker container with write access to `/var/www/html/modules/`
- MariaDB with migration permissions
- PHP 8.1+ with artisan access
- Tesseract.js or API key for receipt OCR
- Stripe API key (when ready to accept payments)
- No paid Akaunting licenses required

## Timeline Estimate
- Phase 1 (Foundation): ~15-20 hours
- Phase 2 (Sales/Docs): ~10-12 hours
- Phase 3 (Banking): ~8-10 hours
- Phase 4 (Projects/HR): ~12-15 hours
- Phase 5 (CRM/Inventory): ~12-15 hours
- Phase 6 (Operations): ~8-10 hours
- **Total: ~65-80 hours of Codex time, ~2-3 weeks**

## Risks
- Akaunting's internal APIs may not be fully documented — may need to reverse-engineer core event hooks
- Double-Entry auto-posting (hooking into invoice/bill creation) requires deep integration with core Document model
- Module UI must match Akaunting's existing Blade/Tailwind component system for consistency
- MariaDB schema additions must not conflict with future Akaunting core updates
- Receipt OCR quality varies — Tesseract is free but less accurate than paid APIs
- 28 Codex tasks is significant — careful task isolation prevents context drift

## Notes
- Feature spec derived from: 37 screenshots, web scraping of all app pages on akaunting.com, plans page feature matrix, and developer documentation
- All modules are native Akaunting Laravel modules — same architecture as OfflinePayments/PaypalStandard
- When done, the system has full parity with Akaunting Ultimate Cloud ($$$) — for free
- Bookie agent integrates via Akaunting's REST API (already exists for core features, modules extend it)
