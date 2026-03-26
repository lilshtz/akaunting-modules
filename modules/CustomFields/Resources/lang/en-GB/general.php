<?php

return [
    'name' => 'Custom Fields',
    'description' => 'Custom fields for all entity types',

    'field_definitions' => 'Field Definitions',
    'field_definition' => 'Field Definition',
    'fields' => 'Field|Fields',

    'entity_type' => 'Entity Type',
    'field_type' => 'Field Type',
    'field_name' => 'Field Name',
    'default_value' => 'Default Value',
    'options' => 'Options',
    'options_hint' => 'One option per line (for select and checkbox fields)',
    'position' => 'Position',
    'show_on_pdf' => 'Show on PDF',
    'width' => 'Width',
    'required_field' => 'Required',

    'entity_types' => [
        'invoice' => 'Invoice',
        'bill' => 'Bill',
        'customer' => 'Customer',
        'vendor' => 'Vendor',
        'item' => 'Item',
        'account' => 'Account',
        'employee' => 'Employee',
        'transfer' => 'Transfer',
        'estimate' => 'Estimate',
        'project' => 'Project',
        'expense_claim' => 'Expense Claim',
    ],

    'field_types' => [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'time' => 'Time',
        'select' => 'Select',
        'checkbox' => 'Checkbox',
        'toggle' => 'Toggle',
        'url' => 'URL',
        'email' => 'Email',
    ],

    'widths' => [
        'full' => 'Full Width',
        'half' => 'Half Width',
    ],

    'no_fields' => 'No custom fields defined for this entity type.',
    'custom_fields' => 'Custom Fields',
];
