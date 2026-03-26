# PRD: BookieBooks — Self-Hosted Accounting Platform

## Problem
Andrew runs 6 entities (AMR, SSS, SSH, Logos, Max Gamma Trust, Personal) and needs a full-featured accounting platform. Akaunting's free tier is too limited, and the paid modules are $300-500 with vendor lock-in. Instead of buying or extending Akaunting, we build our own — purpose-built for Andrew's multi-entity construction/consulting business, fully integrated with the Bookie agent and the OpenClaw ecosystem.

## Goal
Build **BookieBooks** — a self-hosted, full-featured double-entry accounting platform with all the capabilities of Akaunting's paid modules, tailored to Andrew's specific workflows. Runs alongside (or replaces) Akaunting on the server. Bookie agent is the primary operator via API; Andrew accesses via web UI.

## Tech Stack
- **Backend:** Node.js + Express (or Next.js API routes) + TypeScript
- **Frontend:** Next.js + React + Tailwind CSS
- **Database:** PostgreSQL (new container or shared with existing)
- **ORM:** Prisma
- **Auth:** Session-based (single user + optional accountant access)
- **Deployment:** Docker container on clawserv, reverse-proxied
- **API:** RESTful JSON API (Bookie automation) + Web UI (Andrew)
- **Port:** TBD (suggest 8090)

## Entities (Companies)
1. **AM Remodeling Corporation (AMR)** — GC, Cape May, primary revenue
2. **Seven Sigma Strategies LLC (SSS)** — Tech/consulting
3. **Sigma State Holdings LLC (SSH)** — Holding company (WY)
4. **Logos Management LLC** — Management company
5. **Max Gamma Trust** — Trust entity
6. **Personal** — Andrew's personal finances

Each entity is an isolated company with its own COA, ledger, and reporting. Switch between them from a single dashboard.

---

## Feature Modules

### Module 1: Core Platform & Multi-Company
**The foundation everything else builds on.**

- Multi-company support — create/switch/manage companies from one login
- Company settings: name, address, fiscal year, currency, tax ID, logo
- User roles: Owner (Andrew), Accountant (read-only + journal entries), Agent (API-only for Bookie)
- Dashboard per company: cash balance, income/expense chart, recent transactions, overdue invoices
- Global dashboard: consolidated view across all entities
- Settings: localisation, date format, number format, timezone
- Categories: create income/expense/item categories with color coding
- Currencies: multi-currency support with exchange rates
- Tax rates: define tax names, rates, compound taxes, link to items/transactions

**Commit:** `feat: core platform with multi-company and dashboard`

### Module 2: Double-Entry Accounting
**Chart of Accounts, General Ledger, Manual Journals, Trial Balance, Balance Sheet, P&L.**

- **Chart of Accounts (COA)**
  - Account types: Asset, Liability, Equity, Income, Expense
  - Sub-accounts (parent/child hierarchy, unlimited depth)
  - Account codes (numbering system: 1xxx Assets, 2xxx Liabilities, etc.)
  - Enable/disable accounts
  - Default accounts per company (AR, AP, bank, etc.)
  - Import COA from CSV (QuickBooks export compatible)

- **General Ledger**
  - Every transaction auto-posts debit/credit entries
  - Filter by account, date range, accounting basis (cash vs accrual)
  - Running balance per account
  - Export to CSV/PDF

- **Manual Journal Entries**
  - Create entries with multiple debit/credit lines
  - Must balance (total debits = total credits)
  - Attach supporting documents
  - Recurring journal entries
  - Reference number, description, date

- **Trial Balance**
  - Debit/credit columns per account
  - Filter by date range
  - Verify books balance before close

- **Balance Sheet**
  - Assets / Liabilities / Equity sections
  - Hierarchical (follows COA parent/child)
  - As of any date
  - Comparative (this year vs last year)

- **Profit & Loss (Income Statement)**
  - Income vs Expenses with net profit
  - Monthly / Quarterly / Annual views
  - By category breakdown
  - Comparative periods

- **Accounting Basis**
  - Support both cash and accrual basis
  - Per-company setting
  - Reports adjust based on basis

**Commit:** `feat: double-entry accounting with COA, ledger, journals, reports`

### Module 3: Invoicing & Sales
**Create, send, and track invoices.**

- Create invoices with line items (description, quantity, price, tax, discount)
- Invoice numbering (auto-increment, customizable prefix per entity: SSS-1001, AMR-1001)
- Invoice statuses: Draft → Sent → Viewed → Partial → Paid → Overdue → Cancelled
- Email invoices to customers (PDF attachment)
- Client portal: customer views/pays invoices via unique link
- Recurring invoices (weekly, monthly, custom)
- Payment recording (partial payments, overpayments)
- Late payment reminders (auto-email)
- Invoice templates (customizable per entity with logo, colors, footer)
- Attach files to invoices
- Discount: per-line or per-invoice (% or fixed)
- Notes and terms fields
- PDF export / print
- **Cape May specific:** Auto-calculate 20% markup on sub costs for Owner Representative Fee invoices

**Commit:** `feat: invoicing with templates, recurring, client portal`

### Module 4: Bills & Purchases
**Track what you owe.**

- Create bills from vendors (mirror of invoices, but payable)
- Bill statuses: Draft → Received → Partial → Paid → Overdue → Cancelled
- Payment scheduling and tracking
- Recurring bills (rent, insurance, subscriptions)
- Attach documents (scanned bills, PDFs)
- Bill categories map to COA expense accounts
- Vendor management: name, address, email, phone, tax ID, trade, payment terms
- Purchase history per vendor
- **1099 tracking:** Flag vendors as 1099-eligible, track annual payments, generate 1099 summary

**Commit:** `feat: bills, vendor management, 1099 tracking`

### Module 5: Banking & Reconciliation
**Track bank accounts and reconcile transactions.**

- Create unlimited bank/cash accounts per entity
- Track opening and current balances
- Deposits and withdrawals
- Transfers between accounts (intra-entity and inter-entity)
- Bank reconciliation: match imported transactions to recorded ones
- CSV import for bank statements (BoA, Chase, etc. formats)
- Auto-categorization rules (vendor name → category)
- Unmatched transaction queue
- Running reconciled vs unreconciled balance

**Commit:** `feat: banking, transfers, CSV import, reconciliation`

### Module 6: Estimates & Quotes
**Create quotes, get approval, convert to invoices.**

- Create estimates with line items, pricing, tax
- Estimate statuses: Draft → Sent → Viewed → Approved → Refused → Converted → Expired
- Expiration dates
- Customer approval via email link (approve/refuse with notes)
- One-click convert estimate → invoice
- Notification on view/approve/refuse
- Estimate numbering (auto-increment)
- PDF export / email
- Template customization per entity
- **Cape May specific:**
  - Template: "Owner Representative Fee — 20% of sub costs"
  - Line item calculator: input sub invoice amount → auto-output 20% fee
  - Reimbursement line: input cost → auto-output 120% (cost + 20% markup)
  - Track against Adam's retainer balance

**Commit:** `feat: estimates with approval workflow and invoice conversion`

### Module 7: Receipts & Document Management
**OCR receipt capture, auto-categorization, attachment to transactions.**

- Upload receipt images (drag-drop, mobile camera, API upload)
- OCR extraction: vendor, date, amount, tax, payment method, description
- Auto-categorize by vendor name rules
- Auto-assign to entity based on payment method/card
- Create bill/payment from receipt data
- Attach receipt image to transaction
- Duplicate detection (same vendor + amount + date)
- Receipt inbox: queue of unprocessed receipts
- Bulk processing
- Search receipts by vendor, date, amount, category
- **Bookie integration:** Discord drop → API upload → OCR → categorize → create transaction

**Commit:** `feat: receipt management with OCR and auto-categorization`

### Module 8: Projects & Job Costing
**Track project budgets, costs, and profitability.**

- Create projects with: name, client, budget, start/end dates, status
- Milestones with target dates and completion tracking
- Tasks within milestones (assignable, with priority and status)
- Link invoices and bills to projects
- Project P&L: income vs costs per project
- Budget vs actual tracking with variance alerts
- Time tracking: start/stop timer per task, manual entry
- Billing styles: project hours, task hours, fixed rate
- Team members (future: assign to contractors)
- Project dashboard: progress %, budget burn, timeline
- Activity feed per project
- **Cape May project:**
  - Milestones: Site Work → Foundation → Framing → Roofing → MEP → Interior → Punch → Closeout
  - Budget tracking per phase
  - All Cape May transactions auto-linked

**Commit:** `feat: project management with budget tracking and job costing`

### Module 9: Expense Claims
**Contractor/employee expense submission and approval.**

- Submit expense claims with line items and receipt attachments
- Claim statuses: Pending → Approved → Refused → Paid
- Approval workflow (Andrew approves)
- Mark as "Paid by Employee/Contractor" for reimbursement
- Reimbursement tracking
- Category assignment per line item
- Approver notes on refusal
- Summary reports: by employee, by category, by period
- Export for tax documentation

**Commit:** `feat: expense claims with approval workflow`

### Module 10: Employees & Contractors
**HR basics — manage people and their payment info.**

- Add employees/contractors with: name, email, phone, address, hire date, department
- Departments: Masonry, Framing, Plumbing, Electrical, Roofing, HVAC, General, Admin
- Salary/rate info: amount, frequency (hourly/weekly/monthly), payment method
- Bank details for payment
- 1099 vs W-2 classification
- Document storage per person (W-9, insurance cert, license)
- Employee directory with search/filter
- Active/inactive status

**Commit:** `feat: employee and contractor management`

### Module 11: Payroll
**Pay calendars, payslips, benefits/deductions.**

- Pay calendars: weekly, bi-weekly, monthly, custom
- Run payroll: select calendar → review amounts → approve → generate payslips
- Pay items - Benefits: Bonus, Commission, Allowance, Expense Reimbursement
- Pay items - Deductions: Tax withholding, Insurance, Loan repayment, Misc
- Auto-calculate gross → deductions → net pay
- Generate PDF payslips
- Post payroll to journal entries automatically
- Payroll summary reports
- Payment history per employee

**Commit:** `feat: payroll with pay calendars, payslips, benefits/deductions`

### Module 12: CRM (Contacts & Deals)
**Track customer relationships and sales pipeline.**

- Contacts: name, company, email, phone, source, stage, owner
- Companies: name, address, contacts, currency, default stage
- Deals/Opportunities: name, contact, value, stage, expected close date
- Pipeline stages: Lead → Qualified → Proposal → Negotiation → Won → Lost (customizable)
- Activity log: calls, meetings, emails, notes, tasks
- Connect invoices to deals
- Contact → Customer auto-sync
- Growth and activity reports
- Search/filter contacts

**Commit:** `feat: CRM with contacts, deals, pipeline`

### Module 13: Credit/Debit Notes
**Handle returns, adjustments, and disputes.**

- Credit notes: issue to customer, reduce AR balance
- Debit notes: issue to vendor, reduce AP balance
- Link to original invoice/bill
- Apply credit to future invoices
- Refund processing
- Auto-update account balances
- PDF export

**Commit:** `feat: credit and debit notes`

### Module 14: Inventory & Materials
**Track materials, stock levels, and warehouses.**

- Items with: name, SKU, description, category, unit, cost price, sale price
- Item variants (size, type — e.g., 2x6x8, 2x6x16)
- Stock tracking: quantity on hand per warehouse
- Warehouses: Cape May Jobsite, Andrew's Shop
- Stock adjustments (damaged, missing, returned)
- Transfer orders between warehouses
- Barcode generation
- Low stock alerts
- Inventory reports: stock status, value, movement
- Auto-update stock from purchase/sale transactions

**Commit:** `feat: inventory with warehouses, variants, stock tracking`

### Module 15: Budgeting & Forecasting
**Plan and track financial targets.**

- Create budgets per entity, per period (monthly/quarterly/annual)
- Budget by category (map to COA accounts)
- Budget vs actual comparison reports
- Variance alerts (over-budget notifications)
- Income and expense forecasting based on trends
- Project-specific budgets (Cape May phases)
- Wedding budget tracker (special category)
- Visual charts: planned vs actual bar charts

**Commit:** `feat: budgeting with variance tracking and forecasting`

### Module 16: Custom Fields
**Extend any record with custom data.**

- Add custom fields to: invoices, bills, customers, vendors, items, transactions
- Field types: text, number, date, dropdown, checkbox, URL
- Required vs optional
- Show on PDF exports (optional)
- Filter/search by custom fields
- **Default custom fields:**
  - Invoice: PO Number, Job Code
  - Bill: PO Number, Sub Trade, Job Phase
  - Vendor: Trade, License #, Insurance Expiry
  - Item: Supplier, Location

**Commit:** `feat: custom fields for all record types`

### Module 17: Reporting Engine
**Comprehensive financial reporting.**

- Standard reports: P&L, Balance Sheet, Trial Balance, Cash Flow, AR Aging, AP Aging
- Custom report builder: select columns, filters, grouping, date range
- Scheduled reports: auto-generate and email/post weekly/monthly
- Export: PDF, CSV, Excel
- Charts and visualizations
- Comparative reports (period vs period, entity vs entity)
- Tax summary report (for CPA)
- 1099 summary report
- Project profitability report
- Per-entity and consolidated views

**Commit:** `feat: reporting engine with scheduling and export`

### Module 18: Settings & Configuration
**System-wide settings.**

- Company settings: name, address, logo, fiscal year, tax ID, industry
- Localisation: date format, number format, timezone, currency
- Invoice settings: default terms, numbering, prefix, template
- Email service: SMTP config for sending invoices/reminders
- Email templates: customizable for each notification type
- Categories management
- Currency management with exchange rates
- Tax rate management
- Offline payment methods
- Backup & restore

**Commit:** `feat: settings and configuration panel`

---

## API Design (for Bookie Integration)

Every module exposes a RESTful JSON API. Key endpoints:

```
# Auth
POST   /api/auth/login
POST   /api/auth/token          (API key for Bookie)

# Companies
GET    /api/companies
POST   /api/companies
GET    /api/companies/:id
PATCH  /api/companies/:id

# Accounts (COA)
GET    /api/companies/:id/accounts
POST   /api/companies/:id/accounts
PATCH  /api/companies/:id/accounts/:accountId
DELETE /api/companies/:id/accounts/:accountId
POST   /api/companies/:id/accounts/import   (CSV)

# Transactions & Journals
GET    /api/companies/:id/journals
POST   /api/companies/:id/journals
GET    /api/companies/:id/ledger
GET    /api/companies/:id/trial-balance

# Invoices
GET    /api/companies/:id/invoices
POST   /api/companies/:id/invoices
PATCH  /api/companies/:id/invoices/:invoiceId
POST   /api/companies/:id/invoices/:invoiceId/send
POST   /api/companies/:id/invoices/:invoiceId/payments

# Bills
GET    /api/companies/:id/bills
POST   /api/companies/:id/bills
PATCH  /api/companies/:id/bills/:billId
POST   /api/companies/:id/bills/:billId/payments

# Estimates
GET    /api/companies/:id/estimates
POST   /api/companies/:id/estimates
PATCH  /api/companies/:id/estimates/:estimateId
POST   /api/companies/:id/estimates/:estimateId/convert  (→ invoice)

# Receipts
POST   /api/companies/:id/receipts/upload    (image + OCR)
GET    /api/companies/:id/receipts
POST   /api/companies/:id/receipts/:id/process (create transaction)

# Projects
GET    /api/companies/:id/projects
POST   /api/companies/:id/projects
GET    /api/companies/:id/projects/:projectId/pnl
GET    /api/companies/:id/projects/:projectId/budget

# Reports
GET    /api/companies/:id/reports/pnl
GET    /api/companies/:id/reports/balance-sheet
GET    /api/companies/:id/reports/cash-flow
GET    /api/companies/:id/reports/trial-balance
GET    /api/companies/:id/reports/ar-aging
GET    /api/companies/:id/reports/ap-aging

# Contacts / CRM
GET    /api/companies/:id/contacts
POST   /api/companies/:id/contacts
GET    /api/companies/:id/deals
POST   /api/companies/:id/deals

# Employees / Payroll
GET    /api/companies/:id/employees
POST   /api/companies/:id/employees
POST   /api/companies/:id/payroll/run
GET    /api/companies/:id/payroll/history

# Inventory
GET    /api/companies/:id/items
POST   /api/companies/:id/items
GET    /api/companies/:id/warehouses
POST   /api/companies/:id/stock-adjustments

# Budgets
GET    /api/companies/:id/budgets
POST   /api/companies/:id/budgets
GET    /api/companies/:id/budgets/:budgetId/variance
```

**All endpoints require `Authorization: Bearer <api-token>` header.**
**All responses are JSON with consistent pagination, filtering, and sorting.**

---

## Database Schema (High Level)

### Core Tables
- `companies` — id, name, address, logo, fiscal_year_start, currency, tax_id, settings_json
- `users` — id, name, email, password_hash, role
- `user_companies` — user_id, company_id, role (owner/accountant/agent)

### Double-Entry
- `accounts` — id, company_id, parent_id, code, name, type (asset/liability/equity/income/expense), enabled
- `journal_entries` — id, company_id, date, reference, description, status
- `journal_lines` — id, journal_entry_id, account_id, debit, credit, description

### Sales
- `customers` — id, company_id, name, email, phone, address, tax_id, currency
- `invoices` — id, company_id, customer_id, number, status, date, due_date, subtotal, tax, discount, total, notes, terms, recurring_id
- `invoice_items` — id, invoice_id, item_id, description, quantity, price, tax_rate, discount, total
- `payments` — id, company_id, invoice_id, account_id, date, amount, method, reference

### Purchases
- `vendors` — id, company_id, name, email, phone, address, tax_id, trade, is_1099
- `bills` — id, company_id, vendor_id, number, status, date, due_date, subtotal, tax, total, notes
- `bill_items` — id, bill_id, item_id, description, quantity, price, tax_rate, total
- `bill_payments` — id, company_id, bill_id, account_id, date, amount, method, reference

### Banking
- `bank_accounts` — id, company_id, name, number, type, opening_balance, currency
- `bank_transactions` — id, bank_account_id, date, amount, type (deposit/withdrawal), description, category_id, reconciled
- `transfers` — id, from_account_id, to_account_id, amount, date, description

### Estimates
- `estimates` — id, company_id, customer_id, number, status, date, expiry_date, subtotal, tax, total, notes
- `estimate_items` — id, estimate_id, description, quantity, price, tax_rate, total

### Receipts
- `receipts` — id, company_id, image_path, ocr_data_json, vendor, date, amount, category_id, status (pending/processed/matched), transaction_id

### Projects
- `projects` — id, company_id, customer_id, name, description, status, budget, start_date, end_date
- `milestones` — id, project_id, name, target_date, completed
- `project_tasks` — id, milestone_id, name, assignee_id, priority, status, estimated_hours
- `timesheets` — id, task_id, user_id, start, end, hours
- `project_transactions` — project_id, transaction_type, transaction_id (links invoices/bills to projects)

### HR & Payroll
- `employees` — id, company_id, name, email, department, role, salary, salary_type, bank_details, hire_date, is_1099, status
- `departments` — id, company_id, name
- `pay_calendars` — id, company_id, name, frequency, next_run_date
- `payroll_runs` — id, pay_calendar_id, period_start, period_end, status, total
- `payslips` — id, payroll_run_id, employee_id, gross, deductions_json, benefits_json, net, pdf_path

### CRM
- `crm_contacts` — id, company_id, name, email, phone, company_name, source, stage, owner_id
- `crm_deals` — id, company_id, contact_id, name, value, stage, expected_close, status
- `crm_activities` — id, contact_id, deal_id, type (call/meeting/email/note/task), description, date

### Inventory
- `items` — id, company_id, name, sku, description, category_id, cost_price, sale_price, tax_rate
- `item_variants` — id, item_id, name, sku, attributes_json
- `warehouses` — id, company_id, name, address
- `stock` — id, item_id, warehouse_id, quantity
- `stock_adjustments` — id, warehouse_id, item_id, quantity, reason, date
- `transfer_orders` — id, from_warehouse_id, to_warehouse_id, status, date
- `transfer_order_items` — id, transfer_order_id, item_id, quantity

### Budgets
- `budgets` — id, company_id, name, period_start, period_end, type (monthly/quarterly/annual)
- `budget_lines` — id, budget_id, account_id, amount

### Custom Fields
- `custom_field_definitions` — id, company_id, entity_type, field_name, field_type, required, options_json
- `custom_field_values` — id, definition_id, entity_id, value

### Other
- `categories` — id, company_id, name, type (income/expense/item), color
- `currencies` — id, code, name, rate, symbol
- `tax_rates` — id, company_id, name, rate, type (normal/compound)
- `attachments` — id, entity_type, entity_id, file_path, filename, mime_type
- `recurring_templates` — id, company_id, entity_type, entity_id, frequency, next_date, end_date
- `audit_log` — id, company_id, user_id, action, entity_type, entity_id, changes_json, timestamp
- `notifications` — id, user_id, type, message, read, created_at
- `email_templates` — id, company_id, type, subject, body_html
- `settings` — id, company_id, key, value

---

## Acceptance Criteria

### AC1: Core Platform & Auth
- [ ] Multi-company CRUD, switch between companies
- [ ] User auth with API token support for Bookie
- [ ] Dashboard with cash balance, income/expense chart, recent transactions
- [ ] Global multi-entity dashboard
- **Commit:** `feat: core platform with multi-company and auth`

### AC2: Double-Entry Accounting
- [ ] COA with parent/child accounts, import from CSV
- [ ] Manual journal entries that must balance
- [ ] Auto-posting from invoices/bills/payments
- [ ] General Ledger with running balances
- [ ] Trial Balance report
- [ ] Balance Sheet report
- [ ] P&L report (monthly/quarterly/annual)
- **Commit:** `feat: double-entry accounting engine`

### AC3: Invoicing
- [ ] Create/send/track invoices with line items
- [ ] Recurring invoices
- [ ] Payment recording (partial/full)
- [ ] PDF generation with customizable template
- [ ] Email sending
- [ ] Cape May 20% markup auto-calculation
- **Commit:** `feat: invoicing with recurring and PDF generation`

### AC4: Bills & Vendors
- [ ] Bill CRUD with vendor management
- [ ] Payment tracking
- [ ] 1099 vendor flagging and annual summary
- **Commit:** `feat: bills, vendor management, 1099 tracking`

### AC5: Banking
- [ ] Bank accounts with balances
- [ ] CSV import for bank statements
- [ ] Transaction categorization rules
- [ ] Bank reconciliation
- [ ] Inter-account transfers
- **Commit:** `feat: banking with CSV import and reconciliation`

### AC6: Estimates
- [ ] Create/send estimates with approval workflow
- [ ] Convert to invoice on approval
- [ ] Email notifications
- **Commit:** `feat: estimates with approval and invoice conversion`

### AC7: Receipts
- [ ] Upload receipt images via API and web UI
- [ ] OCR extraction (vendor, date, amount)
- [ ] Auto-categorize and create transaction
- [ ] Duplicate detection
- **Commit:** `feat: receipt management with OCR pipeline`

### AC8: Projects
- [ ] Project with milestones, tasks, budget
- [ ] Link invoices/bills to project
- [ ] Project P&L and budget variance
- [ ] Cape May project preconfigured
- **Commit:** `feat: project management with job costing`

### AC9: Expense Claims
- [ ] Submit claims with receipts
- [ ] Approval workflow
- [ ] Reimbursement tracking
- **Commit:** `feat: expense claims with approval workflow`

### AC10: Employees & Payroll
- [ ] Employee/contractor CRUD with departments
- [ ] Pay calendars and payroll runs
- [ ] Payslip PDF generation
- [ ] Benefits/deductions
- **Commit:** `feat: employees and payroll`

### AC11: CRM
- [ ] Contacts, companies, deals pipeline
- [ ] Activity logging
- [ ] Connect invoices to deals
- **Commit:** `feat: CRM with pipeline and activity tracking`

### AC12: Credit/Debit Notes
- [ ] Issue credit/debit notes linked to invoices/bills
- [ ] Auto-adjust balances
- **Commit:** `feat: credit and debit notes`

### AC13: Inventory
- [ ] Items with variants, warehouses, stock tracking
- [ ] Adjustments and transfer orders
- [ ] Low stock alerts
- **Commit:** `feat: inventory management`

### AC14: Budgeting
- [ ] Create budgets by period and category
- [ ] Budget vs actual variance reports
- [ ] Over-budget alerts
- **Commit:** `feat: budgeting and forecasting`

### AC15: Custom Fields
- [ ] Define custom fields per entity type
- [ ] Render on forms and PDFs
- [ ] Filter/search by custom fields
- **Commit:** `feat: custom fields engine`

### AC16: Reporting Engine
- [ ] All standard reports (P&L, BS, CF, TB, AR/AP Aging)
- [ ] Scheduled report generation
- [ ] PDF/CSV export
- [ ] Comparative and consolidated views
- **Commit:** `feat: reporting engine with scheduling`

### AC17: Settings & Email
- [ ] All company/system settings configurable
- [ ] SMTP email sending
- [ ] Email templates
- **Commit:** `feat: settings, email service, templates`

### AC18: Bookie Integration
- [ ] Bookie can create any record via API
- [ ] Receipt drop → OCR → transaction pipeline end-to-end
- [ ] Scheduled report posting to Discord #bookie
- **Commit:** `feat: bookie agent API integration`

---

## Build Order (Codex Task Sequence)

**Phase 1 — Foundation (Tasks 01-05)**
1. Project scaffold: Next.js + Prisma + PostgreSQL + Docker
2. Database schema + migrations
3. Auth system + API token management
4. Multi-company CRUD + settings
5. Dashboard layout + navigation

**Phase 2 — Accounting Core (Tasks 06-10)**
6. Chart of Accounts (COA) with CSV import
7. Journal entries + auto-posting engine
8. General Ledger view
9. Trial Balance + Balance Sheet reports
10. P&L report

**Phase 3 — Sales & Purchases (Tasks 11-15)**
11. Customer management
12. Invoice CRUD + line items + PDF generation
13. Invoice sending + recurring
14. Vendor management + 1099 tracking
15. Bills + bill payments

**Phase 4 — Banking & Receipts (Tasks 16-19)**
16. Bank accounts + transactions
17. CSV import + categorization rules
18. Reconciliation engine
19. Receipt upload + OCR + auto-transaction

**Phase 5 — Estimates & Projects (Tasks 20-23)**
20. Estimates with approval workflow
21. Estimate → Invoice conversion
22. Projects with milestones + tasks
23. Project P&L + budget tracking

**Phase 6 — HR & Payroll (Tasks 24-27)**
24. Employees + departments
25. Expense claims + approval
26. Pay calendars + payroll runs
27. Payslip generation

**Phase 7 — CRM & Inventory (Tasks 28-31)**
28. CRM contacts + deals + pipeline
29. Activity logging
30. Inventory items + variants + warehouses
31. Stock tracking + adjustments + transfers

**Phase 8 — Budgets, Custom Fields, Reporting (Tasks 32-35)**
32. Budget CRUD + variance reports
33. Custom fields engine
34. Reporting engine + scheduled generation
35. Credit/Debit notes

**Phase 9 — Integration & Polish (Tasks 36-38)**
36. Bookie API integration + Discord posting
37. Email service + templates
38. Docker deployment + systemd service

---

## File List
- `PRD.md` — this file
- `tasks/` — atomic task files (01 through 38)
- `src/` — Next.js application source
- `prisma/` — schema + migrations
- `docker/` — Dockerfile + docker-compose
- `docs/` — API documentation

## Dependencies
- PostgreSQL (new Docker container)
- Node.js 20+ (already on server)
- OCR: Tesseract.js or GPT Vision via Bookie's existing pipeline
- SMTP for email sending (Gmail or Resend)
- No external paid services required

## Timeline Estimate
- Phase 1-2: ~12-16 hours (foundation + accounting core)
- Phase 3-4: ~10-14 hours (sales, purchases, banking, receipts)
- Phase 5-6: ~10-12 hours (estimates, projects, HR, payroll)
- Phase 7-8: ~10-12 hours (CRM, inventory, budgets, reporting)
- Phase 9: ~4-6 hours (integration, deployment)
- **Total: ~50-60 hours of Codex time, ~1-2 weeks**

## Risks
- Double-entry engine is the hardest part — accounting math must be correct
- Large number of tables/relationships — schema design is critical
- 38 tasks is a lot of Codex sessions — potential for context drift between tasks
- OCR quality varies — need fallback to manual entry
- Scope creep: each module could easily expand. Stay disciplined on MVP.

## Notes
- This replaces Akaunting entirely (or runs alongside it during transition)
- Feature spec derived from 37 screenshots of Akaunting's paid modules + web scraping
- Docker container runs on clawserv alongside existing services
- Bookie agent is the primary automated user — web UI is for Andrew's manual use
- Name: **BookieBooks** (working title, can change)
