# Task 05: Custom Fields Module

## Context
Standalone module — no dependencies. Adds custom field definitions and values to any Akaunting entity.

## Objective
Build the CustomFields module that lets users define custom fields (text, number, date, select, checkbox, toggle, etc.) on invoices, bills, estimates, customers, vendors, items, accounts, employees, transfers, and any other entity.

## What to Build

### 1. Module Scaffold
Create `/var/www/html/modules/CustomFields/` with standard Akaunting module structure.

### 2. Database Migrations

**Table: `custom_field_definitions`**
- id, company_id, entity_type (varchar — 'invoice', 'bill', 'customer', 'vendor', 'item', 'account', 'employee', 'transfer', 'estimate', 'project', 'expense_claim'), name (varchar 255), field_type (enum: 'text', 'textarea', 'number', 'date', 'datetime', 'time', 'select', 'checkbox', 'toggle', 'url', 'email'), required (boolean default false), default_value (text nullable), options_json (json nullable — for select/checkbox options), position (int default 0), show_on_pdf (boolean default false), width (enum: 'full', 'half' default 'full'), enabled (boolean default true), created_at, updated_at

**Table: `custom_field_values`**
- id, definition_id (FK), entity_type (varchar), entity_id (bigint unsigned), value (text nullable), created_at, updated_at
- Unique index: definition_id + entity_type + entity_id

### 3. Models
- `Models/FieldDefinition.php` — CRUD, scoped by company, has many values
- `Models/FieldValue.php` — belongs to definition, polymorphic entity

### 4. Controllers
- `Http/Controllers/Fields.php` — CRUD for field definitions (list, create, store, edit, update, destroy)
- Group by entity_type in the list view

### 5. Views
- `views/fields/index.blade.php` — List definitions grouped by entity type, with enable/disable toggle
- `views/fields/create.blade.php` — Form: entity type, name, field type, required, default, options (dynamic for select), position, show on PDF, width
- `views/fields/edit.blade.php` — Edit form

### 6. Integration with Entity Forms
Create a Blade component or partial `views/partials/custom-fields-form.blade.php` that:
- Accepts entity_type parameter
- Queries active field definitions for that type + company
- Renders appropriate form inputs (text input, date picker, select dropdown, etc.)
- Pre-fills values if editing existing entity

Create a Blade partial `views/partials/custom-fields-show.blade.php` for read-only display.

### 7. Event Listeners
Listen to core Akaunting form rendering events to inject custom fields into:
- Invoice create/edit forms
- Bill create/edit forms
- Customer create/edit forms
- Vendor create/edit forms
- Item create/edit forms
- Transfer create/edit forms

Listen to entity save/update events to persist custom field values.

### 8. PDF Integration
When `show_on_pdf` is true, inject custom field values into document PDF templates.

### 9. Routes
```php
Route::group(['prefix' => 'custom-fields'], function () {
    Route::resource('fields', 'Fields');
});
```

### 10. Sidebar
Add "Custom Fields" under Settings section in sidebar.

## Verification
1. Create a custom text field on invoices — appears on invoice create form
2. Fill in custom field value, save invoice — value persists
3. Edit invoice — custom field value is pre-filled
4. Create required field — validation prevents saving without it
5. Create select field with options — dropdown renders correctly
6. Field shows on PDF when show_on_pdf enabled
7. Fields can be reordered via position
8. Disabling a field hides it from forms

## Commit Message
`feat(modules): custom fields for all entity types with PDF integration`
