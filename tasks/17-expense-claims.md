# Task 17: Expense Claims Module

## Context
Requires Employees module. Allows employees/contractors to submit expenses for approval.

## Objective
Expense claim submission, approval workflow, reimbursement tracking.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/ExpenseClaims/`

### 2. Database
- `expense_claims` — id, company_id, employee_id (FK), approver_id (FK users), status (draft/submitted/pending/approved/refused/paid), description, total (decimal), due_date (nullable), submitted_at, approved_at, refused_at, refusal_reason (text nullable), paid_at, created_at, updated_at
- `expense_claim_items` — id, claim_id, category_id (FK), date, description, amount (decimal), receipt_path (nullable), notes

### 3. Controllers
- `Claims` — Full CRUD:
  - Create: select employee, add line items with receipt attachments
  - Submit: changes status to submitted, notifies approver
  - Approve: changes status to approved, auto-creates bill/payment for reimbursement
  - Refuse: add reason, notifies employee
  - Pay: mark reimbursement as paid
- `ClaimReports` — Reports by employee, category, period, pending vs approved totals

### 4. Features
- Line items with receipt image attachment per line
- Customizable expense categories (Materials, Travel, Tools, Equipment, Meals, Misc)
- Approval notification (email + in-app)
- PDF export of claim with receipt images
- Import/Export claims
- Due date tracking
- Mark as "Paid by Employee" flag per item

### 5. Sidebar
Add "Expense Claims" under Purchases or HR section.

## Verification
1. Create expense claim with 3 items + receipt images → saves as draft
2. Submit claim → approver notified
3. Approver approves → status changes, bill created for reimbursement
4. Approver refuses with reason → employee notified
5. Mark as paid → tracks reimbursement
6. Report shows totals by employee and category

## Commit Message
`feat(modules): expense claims with approval workflow and reimbursement tracking`
