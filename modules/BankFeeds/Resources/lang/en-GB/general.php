<?php

return [
    'name' => 'Bank Feeds',
    'bank_feed' => 'Bank Feed',
    'bank_feeds' => 'Bank Feeds',
    'transactions' => 'Transactions',
    'import' => 'Import',
    'imports' => 'Imports',
    'import_file' => 'Import File',
    'import_history' => 'Import History',
    'rules' => 'Categorization Rules',
    'rule' => 'Rule',
    'settings' => 'Settings',
    'column_mapping' => 'Column Mapping',
    'bank_feed_summary' => 'Bank Feed Summary',
    'view_transactions' => 'View Transactions',
    'ignore' => 'Ignore',
    'map_columns' => 'Map Columns',
    'apply_rules' => 'Apply Rules',
    'bulk_categorize' => 'Bulk Categorize',
    'reprocess' => 'Re-process',
    'save_mapping' => 'Save mapping for this account',

    'fields' => [
        'bank_account' => 'Bank Account',
        'file' => 'File',
        'format' => 'Format',
        'filename' => 'Filename',
        'row_count' => 'Rows',
        'status' => 'Status',
        'imported_at' => 'Imported At',
        'date' => 'Date',
        'description' => 'Description',
        'amount' => 'Amount',
        'type' => 'Type',
        'category' => 'Category',
        'vendor' => 'Vendor',
        'field' => 'Field',
        'operator' => 'Operator',
        'value' => 'Value',
        'priority' => 'Priority',
        'enabled' => 'Enabled',
        'credit' => 'Credit',
        'debit' => 'Debit',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'complete' => 'Complete',
        'failed' => 'Failed',
        'categorized' => 'Categorized',
        'matched' => 'Matched',
        'ignored' => 'Ignored',
    ],

    'types' => [
        'deposit' => 'Deposit',
        'withdrawal' => 'Withdrawal',
    ],

    'formats' => [
        'csv' => 'CSV',
        'ofx' => 'OFX',
        'qfx' => 'QFX',
    ],

    'operators' => [
        'contains' => 'Contains',
        'equals' => 'Equals',
        'starts_with' => 'Starts With',
        'gt' => 'Greater Than',
        'lt' => 'Less Than',
        'between' => 'Between',
    ],

    'rule_fields' => [
        'description' => 'Description',
        'vendor' => 'Vendor',
        'amount' => 'Amount',
    ],

    'presets' => [
        'generic' => 'Generic CSV',
        'boa' => 'Bank of America',
        'chase' => 'Chase Bank',
        'chase_credit' => 'Chase Credit Card',
    ],

    'messages' => [
        'import_success' => ':count transaction(s) imported successfully. :categorized auto-categorized.',
        'import_failed' => 'Import failed: :error',
        'invalid_format' => 'Invalid file format. Please upload a CSV, OFX, or QFX file.',
        'transaction_ignored' => 'Transaction marked as ignored.',
        'bulk_categorized' => ':count transaction(s) categorized.',
        'no_file' => 'Please select a file to import.',
        'mapping_saved' => 'Column mapping saved for this account.',
        'mapping_deleted' => 'Column mapping deleted.',
    ],

    'help' => [
        'csv_upload' => 'Upload a CSV file from your bank. You will map the columns on the next screen.',
        'ofx_upload' => 'OFX/QFX files are automatically parsed. No column mapping needed.',
        'column_mapping' => 'Map the columns in your CSV to the correct transaction fields.',
        'rules' => 'Rules are applied in priority order. The first matching rule assigns the category and/or vendor.',
        'between_value' => 'For "between" operator, enter two values separated by comma (e.g., 100,500).',
    ],
];
