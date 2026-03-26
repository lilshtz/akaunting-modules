# Task 22: Inventory Module — Items + Warehouses + Stock

## Context
Phase 1 of Inventory. Variants/barcodes/transfers in Task 23.

## Objective
Stock tracking per item per warehouse with auto-update from sales/purchases.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Inventory/`

### 2. Database
- `inventory_warehouses` — id, company_id, name, address, email, phone, enabled, created_at, updated_at
- `inventory_stock` — id, item_id (FK core items), warehouse_id (FK), quantity (decimal 15,4), reorder_level (decimal nullable), created_at, updated_at
- `inventory_history` — id, company_id, item_id, warehouse_id, quantity_change (decimal — positive or negative), type (purchase/sale/adjustment/transfer_in/transfer_out), reference_type, reference_id, description, date, created_at

### 3. Features
- Warehouse CRUD: name, address, contact info
- Stock per item per warehouse (extend core Item model)
- Auto-deduct stock on invoice creation (sale)
- Auto-add stock on bill creation (purchase)
- Stock level display on item detail page
- Low stock alerts (quantity < reorder_level)
- Inventory reports: stock status (current qty per item per warehouse), stock value (qty × cost)
- Inventory history: all stock movements with reason

### 4. Event Listeners
- Invoice created → deduct stock from default warehouse
- Bill created → add stock to default warehouse
- Invoice deleted → restore stock
- Bill deleted → deduct stock

### 5. Sidebar
Add "Inventory" section: Warehouses, Stock.

## Verification
1. Create warehouse "Cape May Jobsite"
2. Set stock for item "2x6x16 SPF" = 40 in warehouse
3. Create invoice selling 5 → stock drops to 35
4. Create bill purchasing 20 → stock increases to 55
5. Low stock alert fires when below reorder level
6. Stock report shows correct quantities and values

## Commit Message
`feat(modules): inventory with warehouses, stock tracking, and auto-update`
