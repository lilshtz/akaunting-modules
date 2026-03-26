# Task 10: Receipts Module with OCR

## Context
Standalone module. Core feature for Bookie agent integration.

## Objective
Upload receipt images, OCR-extract vendor/date/amount/tax, review data, create bill/payment from receipt.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Receipts/`

### 2. Database
**Table: `receipts`** — id, company_id, image_path, thumbnail_path, ocr_raw_json (json nullable), vendor_name (varchar nullable), date (date nullable), amount (decimal 15,4 nullable), tax_amount (decimal 15,4 nullable), currency (varchar 3 nullable), category_id (FK nullable), status (enum: 'uploaded','reviewed','processed','matched'), transaction_id (bigint nullable — links to created bill/payment), notes (text nullable), created_at, updated_at

### 3. OCR Service
`Services/OcrService.php`:
- Primary: Tesseract.js/Tesseract PHP wrapper (free, local)
- Fallback: Taggun or Mindee API (optional, configurable API keys in settings)
- Extract: vendor/merchant name, date, total amount, tax amount, currency
- Return structured JSON

### 4. Controller
- `index()` — Receipt inbox: grid of uploaded receipts with thumbnails, status filter
- `upload()` — Drag-drop upload form, also accepts API POST
- `store()` — Save image, run OCR, save extracted data, set status=uploaded
- `review($id)` — Show extracted data with editable fields, original image side-by-side
- `process($id)` — Create bill or payment from receipt data (select entity, category, account)
- `destroy($id)` — Delete receipt
- `bulkUpload()` — Upload multiple images at once
- `bulkProcess()` — Process all reviewed receipts

### 5. Duplicate Detection
On upload, check for existing receipt with same vendor + amount + date (±3 days). Warn user.

### 6. Auto-Categorization
`Services/CategorizationService.php`:
- Rules: vendor name contains "Home Depot" → category "Materials"
- Store rules in settings or dedicated table
- Auto-assign entity based on payment card/account rules

### 7. API Endpoints (for Bookie)
- `POST /api/receipts/upload` — multipart image upload, returns receipt_id + OCR data
- `GET /api/receipts` — list receipts with filters
- `POST /api/receipts/{id}/process` — create transaction from receipt
- `GET /api/receipts/pending` — unprocessed receipts count

### 8. Settings
Receipt settings page: OCR provider selection, API keys (encrypted), auto-categorization rules.

### 9. Sidebar
Add "Receipts" under Purchases section.

## Verification
1. Upload receipt image → OCR extracts vendor, date, amount
2. Review page shows image + extracted data, edit any field
3. Process receipt → creates bill with correct vendor, amount, category
4. Receipt image attached to resulting transaction
5. Duplicate warning on similar receipt
6. API upload from Bookie works end-to-end
7. Bulk upload processes multiple images

## Commit Message
`feat(modules): receipts with OCR, auto-categorization, and API for Bookie`
