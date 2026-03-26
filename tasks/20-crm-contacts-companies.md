# Task 20: CRM Module — Contacts + Companies

## Context
Phase 1 of CRM. Deals/pipeline in Task 21.

## Objective
CRM contact and company management with auto-sync to Akaunting customers.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Crm/`

### 2. Database
- `crm_contacts` — id, company_id, name, email, phone, crm_company_id (FK nullable), source (web/referral/email/cold/phone/other), stage (lead/subscriber/opportunity/customer), owner_user_id (FK nullable), akaunting_contact_id (FK contacts nullable), notes (text nullable), created_at, updated_at, deleted_at
- `crm_companies` — id, company_id, name, address (text nullable), currency (varchar 3 nullable), default_stage, created_at, updated_at
- `crm_activities` — id, company_id, crm_contact_id (nullable), crm_deal_id (nullable), type (call/meeting/email/note/task), description (text), scheduled_at (datetime nullable), completed_at (datetime nullable), user_id, created_at

### 3. Controllers
- `Contacts` — CRUD with search, filter by stage/source/company
- `Companies` — CRUD with contact list per company
- `Activities` — Log activities on contacts (calls, meetings, emails, notes, tasks)

### 4. Features
- Auto-sync: creating CRM contact auto-creates Akaunting customer contact
- Import contacts from CSV
- Contact detail page: info, activity timeline, linked invoices, linked deals (Task 21)
- Schedule calls and meetings with date/time
- Activity timeline per contact
- Search across all contacts

### 5. Sidebar
Add "CRM" section: Contacts, Companies.

## Verification
1. Create CRM contact → auto-creates Akaunting customer
2. Log a call activity on contact → shows in timeline
3. Schedule meeting → appears with date/time
4. Filter contacts by stage → correct results
5. Import CSV of contacts → all created

## Commit Message
`feat(modules): CRM contacts and companies with activity tracking`
