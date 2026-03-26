# Task 09: Sales & Purchase Orders Module

## Context
Uses Document model. Enhanced by Estimates (convert estimate → SO) and Inventory (stock updates).

## Objective
Sales Orders (customer orders → invoices) and Purchase Orders (vendor orders → bills).

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/SalesPurchaseOrders/`

### 2. Database
- Use core documents table with type='sales-order' and type='purchase-order'
- **Table: `order_settings`** — company_id, so_prefix, so_next_number, po_prefix, po_next_number

### 3. Controllers
- `SalesOrders` — CRUD with line items, customer, delivery date
  - Statuses: Draft → Sent → Confirmed → Issued → Cancelled
  - Convert SO → Invoice (one click)
  - Convert SO → PO (for fulfillment)
  - Email SO as PDF to customer
  - Import/Export
  - Reports: sales history, by customer
- `PurchaseOrders` — CRUD with line items, vendor, delivery date
  - Statuses: Draft → Sent → Confirmed → Received → Cancelled
  - Convert PO → Bill (one click)
  - Email PO as PDF to vendor
  - Import/Export
  - Reports: purchase history, by vendor

### 4. Integration
- If Estimates module installed: convert approved Estimate → Sales Order
- If Inventory module installed: auto-update stock on SO fulfilled / PO received
- Customizable templates
- Attach receipts/files
- Taxes and discounts

### 5. Sidebar
Add "Sales Orders" under Sales, "Purchase Orders" under Purchases.

## Verification
1. Create SO with line items → send to customer → confirm → convert to invoice
2. Create PO with line items → send to vendor → receive → convert to bill
3. Convert estimate to SO works
4. PDF renders with company branding
5. Reports show order history

## Commit Message
`feat(modules): sales and purchase orders with invoice/bill conversion`
