# Task 21: CRM — Deals + Pipeline

## Context
CRM module has contacts from Task 20. Add deals and pipeline.

## Objective
Sales pipeline with customizable stages, deal tracking, and reporting.

## What to Build

### 1. Database
- `crm_pipeline_stages` — id, company_id, name, position, color (varchar), is_won (boolean default false), is_lost (boolean default false), created_at, updated_at
- `crm_deals` — id, company_id, crm_contact_id (FK), name, value (decimal), stage_id (FK pipeline_stages), expected_close (date nullable), status (open/won/lost/deleted), invoice_id (FK nullable), notes, created_at, updated_at, closed_at (nullable)

### 2. Features
- Pipeline stages: fully customizable (create, edit, reorder, set colors)
- Default stages: Lead → Qualified → Proposal → Negotiation → Won | Lost
- Deal CRUD: create deal for contact, set value and expected close
- Pipeline board view: drag-and-drop deals between stages (Kanban)
- Deal detail: contact info, value, stage history, activities, linked invoice
- Win/lose deal → update status and close date
- Connect invoice to deal
- Deal activities (logged on the linked contact)
- Reports: pipeline value by stage, conversion rates, deals won/lost by period, growth report

### 3. Pipeline Board View
Kanban-style columns per stage. Deals as cards showing: name, contact, value, expected close. Drag to move between stages.

### 4. Sidebar
Add "Deals" under CRM section.

## Verification
1. Create custom pipeline stages with colors
2. Create deal → appears in pipeline board
3. Drag deal to next stage → stage updated
4. Mark deal as Won → status changes, show in won report
5. Link invoice to deal → shows in deal detail
6. Pipeline report shows total value per stage

## Commit Message
`feat(modules): CRM deals with kanban pipeline and reporting`
