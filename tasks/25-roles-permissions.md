# Task 25: Roles & Permissions Module

## Context
Extends Akaunting core Role/Permission system with granular per-module access.

## Objective
Custom roles with fine-grained permissions per module and action.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Roles/`

### 2. Database
- `role_module_permissions` — id, role_id (FK), module_alias (varchar), can_view (boolean), can_create (boolean), can_edit (boolean), can_delete (boolean)

### 3. Features
- Create custom roles beyond default Admin
- Predefined templates: Manager (full), Accountant (financial only), Employee (limited), Customer (portal)
- Per-module permission matrix: for each installed module, set view/create/edit/delete
- Permission check middleware: enforce on all module routes
- User role assignment per company
- Role management UI: create, edit, clone, delete roles
- Permission matrix view: grid of modules × actions with checkboxes

### 4. Integration
- All module controllers check permissions before allowing actions
- Sidebar items hidden if user lacks view permission for that module
- API endpoints respect role permissions

## Verification
1. Create "Accountant" role with view-only on invoices, full on journals
2. Assign role to user → user can view invoices but not edit, can create journals
3. Sidebar hides modules user can't access
4. API returns 403 for unauthorized actions

## Commit Message
`feat(modules): roles and permissions with per-module granular access control`
