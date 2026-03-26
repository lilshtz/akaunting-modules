# PRD: Akaunting Full-Feature Setup & Bookie Automation

## Problem
Andrew runs 6 entities (AMR, SSS, SSH, Logos, Max Gamma Trust, Personal) across construction, tech/consulting, and holding structures. Akaunting is deployed self-hosted at `http://localhost:8085` but is minimally configured — only 3 companies created (AMR, SSS, MC Remodeling). Most paid modules are not installed. The Bookie agent exists but can't leverage Akaunting's full capabilities without the platform being properly set up.

The current state is: raw receipt photos → manual OCR → basic transaction entry. No double-entry, no estimates, no payroll, no project tracking, no bank feeds, no CRM, no expense claims, no budgeting. Andrew is flying blind on multi-entity financials.

## Goal
Transform Akaunting into a fully-featured accounting platform for all 6 entities with every relevant module installed, configured, and integrated with Bookie's automated pipeline. Andrew should be able to drop receipts in Discord and have them flow through to proper double-entry books — and pull financial reports for any entity at any time.

## Entities (Akaunting Companies)
1. **AM Remodeling Corporation (AMR)** — GC, Cape May project, primary revenue
2. **Seven Sigma Strategies LLC (SSS)** — Tech/consulting (Stripe suspended, minimal activity)
3. **Sigma State Holdings LLC (SSH)** — Holding company (WY)
4. **Logos Management LLC** — Management company
5. **Max Gamma Trust** — Trust entity
6. **Personal** — Andrew's personal finances

## Modules to Install & Configure

### Tier 1 — CRITICAL (install first)

#### 1. Double-Entry Accounting
- **What:** Chart of Accounts, General Ledger, Balance Sheet, Trial Balance, P&L, Manual Journals
- **Why:** Foundation for everything else. Can't do real accounting without it.
- **Config per entity:**
  - Set up COA per entity (map from existing QuickBooks exports where available)
  - Define account types: Assets, Liabilities, Equity, Income, Expenses
  - Set fiscal year (calendar year for all entities)
  - Enable accrual basis for AMR (construction), cash basis for others
  - Set up inter-entity accounts (Logos→SSS, Logos→AMR transfers)
- **Bookie automation:**
  - All receipt entries auto-create journal entries
  - Monthly close process: trial balance review, journal adjustments
  - Auto-generate P&L and Balance Sheet reports monthly

#### 2. Estimates (Quotes)
- **What:** Create quotes, get customer approval, convert to invoices
- **Why:** Cape May billing model (20% of sub costs). Andrew needs to generate estimates for Adam, get approval, convert to invoices.
- **Config:**
  - Template with SSS branding (Owner Representative Fee)
  - Line items: Sub cost markup (20%), Reimbursements (120%)
  - Expiration dates, approval workflow
  - Email notifications on view/approve/refuse
- **Bookie automation:**
  - Generate estimates from sub invoices (SJ Hauck, Carlos Cruz, etc.)
  - Auto-calculate 20% markup
  - Convert approved estimates to invoices
  - Track Adam's retainer balance ($10K start, ~$3K remaining)

#### 3. Receipts (Document Scanning)
- **What:** OCR receipt capture, auto-categorization
- **Why:** Bookie's core pipeline — 416 backlog images + ongoing drops
- **Config:**
  - Category mapping per entity
  - Auto-attach scanned documents to transactions
  - Receipt → bill/payment auto-creation
- **Bookie automation:**
  - Discord drop → OCR → Akaunting Receipt API
  - Auto-categorize by vendor/entity
  - Flag duplicates
  - Bulk backlog processing

#### 4. Custom Fields
- **What:** Add custom fields to invoices, bills, customers, items
- **Why:** Track construction-specific data (PO numbers, job codes, entity assignment)
- **Config:**
  - Invoice: PO Number, Job Code, Entity
  - Bill: PO Number, Sub Trade, Job Phase
  - Customer: Entity Type, Tax ID
  - Vendor: Trade, License #, Insurance Expiry
- **Bookie automation:**
  - Auto-populate custom fields from receipt OCR data
  - Filter/report by custom fields

### Tier 2 — HIGH PRIORITY (install second)

#### 5. Projects
- **What:** Project management with tasks, milestones, timesheets, invoicing
- **Why:** Cape May is a multi-month construction project. Track costs, progress, sub invoices against project budget.
- **Config:**
  - Project: "Cape May - 103 Beach Ave" under AMR
  - Client: Adam Veitenheimer
  - Milestones: Foundation → Framing → Roofing → MEP → Interior → Closeout
  - Budget tracking: planned vs actual per phase
  - Connect all Cape May invoices/bills to project
- **Bookie automation:**
  - Auto-link Cape May receipts/bills to project
  - Generate project P&L on demand
  - Track budget variance alerts
  - Future projects: create from template

#### 6. Expense Claims
- **What:** Employee/contractor expense submission, approval, reimbursement
- **Why:** Andrew has contractors who buy materials and need reimbursement. Track what's reimbursable vs what Adam pays directly.
- **Config:**
  - Approval workflow (Andrew approves)
  - Categories: Materials, Travel, Tools, Equipment, Misc
  - Mark as "Paid by Employee" for reimbursement tracking
  - Export for tax documentation
- **Bookie automation:**
  - Contractors submit via Discord or direct upload
  - Auto-route for Andrew's approval
  - Track reimbursement status

#### 7. Employees (FREE)
- **What:** Employee/contractor management, departments, salary info
- **Why:** Required by Payroll module. Track subs and their payment info.
- **Config:**
  - Add contractors: Carlos Cruz, SJ Hauck crew, etc.
  - Departments: Masonry, Framing, Plumbing, Electrical, Roofing, General
  - Payment info: rates, payment method
  - Track 1099 status
- **Bookie automation:**
  - Auto-create vendor/employee records from new sub invoices
  - 1099 tracking and year-end reporting

#### 8. Payroll
- **What:** Pay calendars, payslips, benefits/deductions
- **Why:** When Andrew has W-2 employees (even himself via AMR), need proper payroll tracking.
- **Config:**
  - Pay calendars: Monthly (AMR owner draw), per-project (subs)
  - Pay items: Bonus, Commission, Allowance, Benefits, Expense Reimbursement
  - Deductions: Taxes, Insurance
  - Auto-generate payslips
- **Bookie automation:**
  - Run payroll on schedule
  - Generate payslips as PDF
  - Post to journal entries automatically

### Tier 3 — IMPORTANT (install third)

#### 9. CRM
- **What:** Contact management, leads, deals, pipelines
- **Why:** Track clients (Adam, future clients), sub relationships, vendor contacts.
- **Config:**
  - Pipeline stages: Lead → Estimate → Contract → Active → Complete → Paid
  - Contacts: Adam V, all subs, all vendors, all suppliers
  - Connect invoices to deals
  - Activity logging (calls, meetings, emails)
- **Bookie automation:**
  - Auto-create contacts from invoice/bill data
  - Track payment history per contact
  - Flag overdue accounts

#### 10. Credit/Debit Notes
- **What:** Issue credit/debit notes for returns, adjustments, disputes
- **Why:** Handle disputes like SJ Hauck scope disagreement. Proper accounting for adjustments.
- **Config:**
  - Templates matching invoice branding
  - Auto-update customer account balances
  - Link to original invoices
- **Bookie automation:**
  - Generate from dispute resolution
  - Auto-adjust AR/AP balances

#### 11. Inventory
- **What:** Stock management, warehouses, variants, barcodes, transfer orders
- **Why:** Track materials for Cape May (lumber, block, rebar, etc.). Know what's on-site vs ordered.
- **Config:**
  - Warehouse: "Cape May Jobsite" + "Andrew's Shop"
  - Items: lumber (by size/type), block, rebar, hardware, roofing materials
  - Track by supplier (Carter Lumber, ABC Supply, etc.)
  - Reorder alerts for recurring materials
- **Bookie automation:**
  - Auto-update inventory from purchase receipts
  - Generate material reports for project costing
  - Flag understocked items

#### 12. Budgeting
- **What:** Budget planning, income/expense forecasting, variance tracking
- **Why:** Cape May budget tracking, wedding budget ($X remaining), personal budget.
- **Config:**
  - Cape May project budget (by phase)
  - Wedding budget (countdown to June 6)
  - Personal monthly budget
  - Entity-level annual budgets
- **Bookie automation:**
  - Monthly budget vs actual reports
  - Alert on overspend
  - Forecasting based on trends

### Tier 4 — NICE TO HAVE (install when needed)

#### 13. Bank Feeds
- **What:** Auto-import bank transactions for reconciliation
- **Why:** Eliminate manual transaction entry for bank/card accounts
- **Note:** May be Cloud-only feature. If self-hosted supports it via Plaid/OFX, configure. Otherwise, use CSV import automation.
- **Config:**
  - Connect BoA checking, credit cards
  - Auto-categorization rules
  - Reconciliation workflow
- **Bookie automation:**
  - Daily import and categorization
  - Match receipts to bank transactions
  - Flag unmatched transactions

#### 14. Sales & Purchase Orders
- **What:** Create/manage sales orders and purchase orders
- **Why:** Formalize material ordering (Carter Lumber POs) and client work orders
- **Config:**
  - PO templates with job/entity fields
  - Auto-convert POs to bills on receipt
  - SO to invoice conversion
- **Bookie automation:**
  - Generate POs from material needs
  - Track PO fulfillment

#### 15. Point of Sale (POS)
- **What:** In-person payment processing
- **Why:** Low priority. Only relevant if Andrew starts taking payments on-site.
- **Skip for now.**

## Technical Approach

### Phase 1: Module Installation (Bookie task)
1. Purchase required modules from Akaunting marketplace (Andrew provides payment)
2. Install via Akaunting admin panel or CLI (`php artisan module:install`)
3. Verify each module loads correctly
4. Document installed versions

### Phase 2: Entity Setup (Bookie task)
1. Create missing companies: SSH, Logos, Max Gamma Trust, Personal
2. Rename "MC Remodeling" → verify it's AMR
3. Set fiscal year, currency, tax settings per entity
4. Configure Chart of Accounts per entity (from QB exports where available)
5. Set up inter-entity accounts

### Phase 3: Module Configuration (Bookie task)
1. Configure each module per the specs above
2. Set up custom fields
3. Create project(s) — Cape May first
4. Add employees/contractors
5. Configure estimate/invoice templates with proper branding
6. Set up approval workflows

### Phase 4: Automation Pipeline (Codex build)
1. **Receipt Pipeline v2:** Discord drop → OCR → Akaunting Receipt API → auto-categorize → journal entry
2. **Estimate Generator:** Sub invoice in → calculate 20% → generate estimate → send to Adam
3. **Report Scheduler:** Monthly P&L, Balance Sheet, Budget variance per entity → post to #bookie
4. **Reconciliation Engine:** Match receipts to bank transactions, flag discrepancies
5. **Project Cost Tracker:** Auto-link Cape May transactions, budget variance alerts

### Phase 5: Data Migration
1. Import 416 backlog receipts through new pipeline
2. Import existing QB data where available
3. Reconcile opening balances
4. Verify trial balance for each entity

## Acceptance Criteria

### AC1: All Modules Installed ✅
- [ ] Double-Entry, Estimates, Receipts, Custom Fields, Projects, Expense Claims, Employees, Payroll, CRM, Credit/Debit Notes, Inventory, Budgeting installed and active
- **Commit:** `feat: install all akaunting modules`

### AC2: All 6 Entities Configured ✅
- [ ] AMR, SSS, SSH, Logos, Max Gamma Trust, Personal — each with proper COA, fiscal year, tax settings
- [ ] Inter-entity accounts set up
- **Commit:** `feat: configure all 6 entities with COA and settings`

### AC3: Double-Entry Working ✅
- [ ] COA populated per entity
- [ ] Can create manual journal entries
- [ ] General Ledger, Trial Balance, Balance Sheet, P&L reports generate correctly
- **Commit:** `feat: double-entry accounting configured per entity`

### AC4: Estimates → Invoice Flow ✅
- [ ] Can create estimate with 20% markup calculation
- [ ] Adam can view/approve via client portal or email
- [ ] Approved estimates convert to invoices
- **Commit:** `feat: estimate-to-invoice workflow for Cape May billing`

### AC5: Receipt Pipeline v2 ✅
- [ ] Discord receipt drop → OCR → Akaunting entry (auto-categorized, proper entity)
- [ ] Receipt image attached to transaction
- [ ] Journal entry auto-created
- **Commit:** `feat: receipt pipeline v2 with akaunting API integration`

### AC6: Cape May Project Tracking ✅
- [ ] Project created with milestones, budget
- [ ] All Cape May bills/invoices linked to project
- [ ] Project P&L and budget variance reports available
- **Commit:** `feat: cape may project with budget tracking`

### AC7: Employee/Contractor Management ✅
- [ ] All active subs added with trade, rate, payment info
- [ ] 1099 tracking enabled
- [ ] Pay calendars created for recurring payments
- **Commit:** `feat: employee and contractor management`

### AC8: Monthly Reporting Automated ✅
- [ ] P&L per entity generated monthly
- [ ] Balance Sheet per entity generated monthly
- [ ] Budget vs actual comparison
- [ ] Reports posted to #bookie Discord
- **Commit:** `feat: automated monthly financial reporting`

### AC9: Backlog Migration ✅
- [ ] 416 receipt images processed through pipeline
- [ ] Categorized by entity
- [ ] Opening balances reconciled
- **Commit:** `feat: receipt backlog migration complete`

## File List
- `PRD.md` — this file
- `tasks/` — atomic task files for Codex
- `scripts/` — automation scripts for Bookie
- `config/` — COA templates, custom field definitions, entity configs
- `docs/` — API reference notes, module documentation

## Dependencies
- Andrew purchases required Akaunting modules (est. $300-500 self-hosted lifetime)
- QuickBooks COA exports for AMR and SSS (if not already in Bookie's workspace)
- Akaunting REST API access from Bookie (verify auth token)
- Bookie agent updated with new API endpoints and workflows

## Timeline Estimate
- Phase 1-2: 2-3 hours (module install + entity setup)
- Phase 3: 4-6 hours (module configuration)
- Phase 4: 8-12 hours (automation pipeline build — Codex tasks)
- Phase 5: 4-6 hours (data migration + reconciliation)
- **Total: ~20-25 hours of agent time across Bookie + Codex**

## Risks
- Some modules may require specific Akaunting version (currently v3.0.0 per screenshots)
- Bank Feeds may be Cloud-only — need to verify self-hosted support
- Payroll module requires Employees module as dependency
- Large receipt backlog may have OCR quality issues on older/faded receipts
- Inter-entity transactions need careful COA mapping to avoid double-counting

## Notes
- This PRD was built from 37 screenshots of Akaunting's feature set + web scraping of akaunting.com/apps
- Akaunting is Laravel/VueJS/Tailwind with RESTful API — all automation can use the API
- Self-hosted at localhost:8085 on Docker
- Bookie agent already has receipt OCR pipeline — this extends it significantly
