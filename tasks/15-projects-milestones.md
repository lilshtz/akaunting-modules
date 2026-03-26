# Task 15: Projects Module — Projects + Milestones + Tasks

## Context
Phase 1 of Projects. Time tracking and budget come in Task 16.

## Objective
Project management with milestones, tasks, team assignment, discussions.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Projects/`

### 2. Database
- `projects` — id, company_id, contact_id (FK — client), name, description (text), status (active/completed/on_hold/cancelled), billing_type (project_hours/task_hours/fixed_rate), billing_rate (decimal nullable), budget (decimal nullable), start_date, end_date (nullable), created_at, updated_at, deleted_at
- `project_milestones` — id, project_id, name, description, target_date, completed_at (nullable), position
- `project_tasks` — id, milestone_id (nullable), project_id, name, description, assignee_id (FK employees nullable), priority (low/medium/high/critical), status (todo/in_progress/review/done), estimated_hours (decimal nullable), position, created_at, updated_at
- `project_members` — project_id, user_id, role (manager/member)
- `project_discussions` — id, project_id, user_id, body (text), created_at, updated_at
- `project_transactions` — id, project_id, document_type (invoice/bill), document_id (FK)
- `project_activities` — id, project_id, user_id, action, entity_type, entity_id, description, created_at

### 3. Controllers
- `Projects` — CRUD + dashboard per project
  - Dashboard: progress %, milestones timeline, recent tasks, activity feed, transaction summary
  - Link invoices/bills to project (select from existing or create new)
- `Milestones` — CRUD within project context
- `Tasks` — CRUD with status transitions, assignee, priority
- `Discussions` — Create/reply within project

### 4. Views
- Project list with status badges, budget, progress
- Project dashboard (single project view with tabs: Overview, Tasks, Milestones, Transactions, Discussions, Activity)
- Milestone timeline view
- Task board (kanban-style) or list view
- Discussion thread

### 5. Sidebar
Add "Projects" section with link to project list.

### 6. Activity Logging
Auto-log: project created, task status changed, milestone completed, transaction linked, discussion posted.

## Verification
1. Create project "Cape May" with client "Adam", budget $500K
2. Add milestones: Foundation, Framing, Roofing
3. Add tasks under milestones with assignees and priorities
4. Link existing invoice to project → shows in transactions tab
5. Post discussion → appears in discussion tab
6. Activity feed shows all changes
7. Project dashboard shows progress overview

## Commit Message
`feat(modules): projects with milestones, tasks, discussions, and activity tracking`
