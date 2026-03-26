<?php

return [
    'name' => 'Receipts',
    'receipt' => 'Receipt',
    'receipts' => 'Receipts',
    'receipt_inbox' => 'Receipt Inbox',
    'upload_receipt' => 'Upload Receipt',
    'receipt_summary' => 'Receipt Summary',
    'review' => 'Review',
    'process' => 'Process',
    'settings' => 'Settings',
    'categorization_rule' => 'Categorization Rule',
    'bulk_upload' => 'Bulk Upload',
    'bulk_process' => 'Bulk Process',

    'fields' => [
        'vendor_name' => 'Vendor Name',
        'receipt_date' => 'Receipt Date',
        'amount' => 'Amount',
        'tax_amount' => 'Tax Amount',
        'currency' => 'Currency',
        'category' => 'Category',
        'status' => 'Status',
        'image' => 'Receipt Image',
        'notes' => 'Notes',
        'ocr_provider' => 'OCR Provider',
        'api_key' => 'API Key',
        'vendor_pattern' => 'Vendor Pattern',
        'account' => 'Account',
        'priority' => 'Priority',
        'entity_type' => 'Create As',
        'contact' => 'Vendor/Contact',
    ],

    'statuses' => [
        'uploaded' => 'Uploaded',
        'reviewed' => 'Reviewed',
        'processed' => 'Processed',
        'matched' => 'Matched',
    ],

    'ocr_providers' => [
        'tesseract' => 'Tesseract (Local/Free)',
        'taggun' => 'Taggun API',
        'mindee' => 'Mindee API',
    ],

    'entity_types' => [
        'bill' => 'Bill',
        'payment' => 'Payment/Expense',
    ],

    'messages' => [
        'duplicate_warning' => 'Warning: :count potential duplicate receipt(s) found.',
        'already_processed' => 'This receipt has already been processed.',
        'processed_success' => 'Receipt processed successfully. Transaction created.',
        'bulk_uploaded' => ':count receipt(s) uploaded successfully.',
        'bulk_duplicates' => ':count receipt(s) may be duplicates.',
        'bulk_processed' => ':count receipt(s) processed successfully.',
        'created_from_receipt' => 'Created from receipt #:id',
        'no_receipts' => 'No receipts found.',
        'drag_drop' => 'Drag & drop receipt images here or click to browse',
        'ocr_extracting' => 'Extracting data from receipt...',
        'review_extracted' => 'Review and correct the extracted data below.',
        'select_entity_type' => 'Choose whether to create a bill or direct payment.',
    ],

    'actions' => [
        'upload' => 'Upload Receipt',
        'review' => 'Review & Edit',
        'process' => 'Create Transaction',
        'bulk_upload' => 'Upload Multiple',
        'bulk_process' => 'Process All Reviewed',
    ],
];
