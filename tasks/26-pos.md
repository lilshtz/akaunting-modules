# Task 26: Point of Sale Module

## Context
Standalone module. Enhanced by Inventory for stock deduction.

## Objective
POS interface for processing walk-in sales with receipt printing.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Pos/`

### 2. Database
- `pos_orders` — id, company_id, contact_id (FK nullable), order_number, status (completed/refunded/cancelled), subtotal, tax, discount, total, payment_method, paid_amount, change_amount, tab_name (nullable), created_at, updated_at
- `pos_order_items` — id, order_id, item_id (FK nullable), name, sku, quantity, price, discount, tax, total
- `pos_settings` — company_id, receipt_width (int), default_payment_method, auto_create_invoice (boolean), next_order_number

### 3. Features
- POS interface: full-screen product grid/list, search, barcode scanner input
- Order basket: add items, adjust quantity, apply per-item discount
- Multiple tabs: serve multiple customers simultaneously (each tab = separate order)
- Customer selection (optional): search existing contacts
- Payment: select method (cash/card/split), enter amount paid, calculate change
- Receipt: generate thermal-style receipt (configurable width)
- Print/download/email receipt
- Order history: list with search, status filter
- Refund processing: select order, select items to refund
- Bill splitting
- Auto-create Akaunting invoice from POS order (configurable)
- Daily sales summary report

### 4. If Inventory installed
- Auto-deduct stock from default warehouse on sale
- Show stock level next to items

### 5. Sidebar
Add "POS" section.

## Verification
1. Open POS → see product grid
2. Add 3 items to basket → totals calculate correctly
3. Process payment as cash → receipt generated
4. Open new tab → serve second customer simultaneously
5. Refund an item → order updated, stock restored
6. Order history shows all processed orders

## Commit Message
`feat(modules): point of sale with multi-tab, barcode scanning, and receipts`
