<?php

return [
    'name' => 'Credit & Debit Notes',
    'credit_notes' => 'Credit Notes',
    'credit_note' => 'Credit Note',
    'debit_notes' => 'Debit Notes',
    'debit_note' => 'Debit Note',
    'credit_debit_summary' => 'Credit & Debit Notes Summary',

    // Fields
    'note_number' => 'Note Number',
    'note_date' => 'Note Date',
    'due_date' => 'Due Date',
    'linked_invoice' => 'Linked Invoice',
    'linked_bill' => 'Linked Bill',
    'customer' => 'Customer',
    'vendor' => 'Vendor',
    'currency' => 'Currency',
    'reason' => 'Reason for Issuance',

    // Line items
    'line_items' => 'Line Items',
    'item_name' => 'Item Name',
    'description' => 'Description',
    'quantity' => 'Quantity',
    'price' => 'Price',
    'tax' => 'Tax',
    'discount' => 'Discount',
    'amount' => 'Amount',
    'add_item' => 'Add Item',
    'sub_total' => 'Sub Total',
    'total' => 'Total',
    'discount_rate' => 'Discount Rate',
    'discount_type' => 'Discount Type',
    'notes' => 'Notes',
    'footer' => 'Footer',
    'available_credit' => 'Available Credit',
    'applied_amount' => 'Applied Amount',

    // Statuses
    'statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'open' => 'Open',
        'partial' => 'Partial',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    // Actions
    'actions' => [
        'send' => 'Send',
        'mark_open' => 'Mark as Open',
        'cancel' => 'Cancel',
        'apply_credit' => 'Apply Credit',
        'make_refund' => 'Make Refund',
        'convert_to_invoice' => 'Convert to Invoice',
        'convert_to_bill' => 'Convert to Bill',
        'download_pdf' => 'Download PDF',
    ],

    // Messages
    'messages' => [
        'cn_created' => 'Credit note :number created as draft.',
        'cn_updated' => 'Credit note :number updated.',
        'cn_sent' => 'Credit note :number sent to :email.',
        'dn_created' => 'Debit note :number created as draft.',
        'dn_updated' => 'Debit note :number updated.',
        'dn_sent' => 'Debit note :number sent to :email.',
        'sent_success' => 'Note sent successfully.',
        'marked_open' => 'Note marked as open.',
        'cancelled' => 'Note has been cancelled.',
        'not_editable' => 'This note can only be edited in Draft status.',
        'not_deletable' => 'Only draft notes can be deleted.',
        'credit_applied' => ':amount applied to invoice :invoice.',
        'credit_applied_success' => 'Credit applied successfully.',
        'exceeds_available' => 'Amount exceeds available credit.',
        'refund_recorded' => 'Refund of :amount recorded.',
        'refund_success' => 'Refund recorded successfully.',
        'converted_to_invoice' => 'Converted to invoice :invoice.',
        'converted_success' => 'Successfully converted to invoice :invoice.',
        'converted_to_bill' => 'Converted to bill :bill.',
        'converted_bill_success' => 'Successfully converted to bill :bill.',
    ],

    // Portal
    'portal' => [
        'title' => 'Note',
        'from' => 'From',
        'to' => 'To',
        'powered_by' => 'Powered by',
    ],

    // Notifications
    'notifications' => [
        'cn_sent_subject' => 'Credit Note :number from :company',
        'dn_sent_subject' => 'Debit Note :number from :company',
        'greeting' => 'Dear :name,',
        'cn_sent_body' => ':company has issued credit note :number for :amount.',
        'dn_sent_body' => ':company has issued debit note :number for :amount.',
        'view_credit_note' => 'View Credit Note',
        'view_debit_note' => 'View Debit Note',
        'footer' => 'Please review the attached note. Contact us if you have any questions.',
    ],

    // Reports
    'report' => [
        'outstanding_credits' => 'Outstanding Credits by Customer',
        'notes_by_period' => 'Notes Issued by Period',
        'total_credit_notes' => 'Total Credit Notes',
        'total_debit_notes' => 'Total Debit Notes',
        'total_credit_value' => 'Total Credit Value',
        'total_debit_value' => 'Total Debit Value',
        'outstanding_credit_value' => 'Outstanding Credit Value',
    ],

    // Timeline
    'timeline' => 'Timeline',
    'no_history' => 'No history yet.',

    // Empty states
    'no_credit_notes' => 'No credit notes found.',
    'no_debit_notes' => 'No debit notes found.',

    // Apply credit
    'apply_to_invoice' => 'Apply to Invoice',
    'select_invoice' => 'Select Invoice',
    'amount_to_apply' => 'Amount to Apply',
    'refund_amount' => 'Refund Amount',
    'credit_applications' => 'Credit Applications',
    'applied_on' => 'Applied On',
    'portal_link' => 'Portal Link',
];
