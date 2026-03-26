# Task 23: Inventory — Variants + Barcodes + Adjustments + Transfers

## Context
Inventory has basic stock from Task 22. Add variants, barcodes, adjustments, transfers.

## Objective
Item variants (sizes/colors), barcode generation, stock adjustments, and warehouse transfers.

## What to Build

### 1. Database
- `inventory_variants` — id, item_id, name, sku (unique), attributes_json (e.g., {"size":"2x6x16","grade":"SPF"}), cost_price (decimal nullable), sale_price (decimal nullable), created_at, updated_at
- `inventory_adjustments` — id, company_id, warehouse_id, item_id, variant_id (nullable), quantity (decimal — positive to add, negative to remove), reason (damaged/missing/stolen/returned/recount/other), description, date, user_id, created_at
- `inventory_transfer_orders` — id, company_id, from_warehouse_id, to_warehouse_id, status (draft/in_transit/received/cancelled), date, description, created_at, updated_at
- `inventory_transfer_items` — id, transfer_order_id, item_id, variant_id (nullable), quantity
- `inventory_item_groups` — id, company_id, name, description
- `inventory_item_group_items` — item_group_id, item_id

### 2. Features
- Variants: create variants per item with unique SKU and optional price overrides
- Stock tracked per variant per warehouse
- Barcode generation: auto-generate Code128/EAN13 barcodes per item/variant
- Print barcode labels (configurable format)
- Stock adjustments: record damaged/missing/stolen items with reason
- Transfer orders: create order, list items/quantities, ship, receive
- Item groups: group related items (e.g., all lumber types)

### 3. Controllers
- Variants (nested under items), Adjustments, TransferOrders, ItemGroups

### 4. Reports
- Stock by variant
- Adjustment history
- Transfer history
- Item group summary

## Verification
1. Create item variants (2x6x8, 2x6x10, 2x6x16) with different SKUs
2. Generate barcode for each → renders correctly
3. Create stock adjustment (5 units damaged) → stock decreases
4. Create transfer order from "Shop" to "Cape May" → items transfer on receive
5. Item group shows all lumber items together

## Commit Message
`feat(modules): inventory variants, barcodes, adjustments, and transfer orders`
