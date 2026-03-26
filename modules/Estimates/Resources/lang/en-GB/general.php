<?php

return [
    'name' => 'Estimates',
    'estimates' => 'Estimates',
    'estimate' => 'Estimate',
    'estimate_summary' => 'Estimate Summary',
    'settings' => 'Estimate Settings',
    'new_estimate' => 'New Estimate',
    'edit_estimate' => 'Edit Estimate',
    'estimate_details' => 'Estimate Details',
    'estimate_number' => 'Estimate Number',
    'estimate_date' => 'Estimate Date',
    'expiry_date' => 'Expiry Date',
    'customer' => 'Customer',
    'currency' => 'Currency',
    'category' => 'Category',

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
    'remove_item' => 'Remove Item',
    'sub_total' => 'Sub Total',
    'total' => 'Total',
    'discount_rate' => 'Discount Rate',
    'discount_type' => 'Discount Type',

    // Fields
    'title' => 'Title',
    'subheading' => 'Subheading',
    'notes' => 'Notes / Terms',
    'footer' => 'Footer',
    'terms' => 'Terms & Conditions',

    // Statuses
    'statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'viewed' => 'Viewed',
        'approved' => 'Approved',
        'refused' => 'Refused',
        'converted' => 'Converted',
        'expired' => 'Expired',
    ],

    // Actions
    'actions' => [
        'send' => 'Send to Customer',
        'approve' => 'Approve',
        'refuse' => 'Refuse',
        'convert' => 'Convert to Invoice',
        'duplicate' => 'Duplicate',
        'download_pdf' => 'Download PDF',
        'mark_approved' => 'Mark as Approved',
        'mark_refused' => 'Mark as Refused',
    ],

    // Settings
    'prefix' => 'Number Prefix',
    'next_number' => 'Next Number',
    'default_terms' => 'Default Terms',
    'template' => 'Template',
    'approval_required' => 'Approval Required',

    // Timeline / History
    'timeline' => 'Timeline',
    'history' => 'History',
    'created_date' => 'Created',
    'sent_date' => 'Sent',
    'viewed_date' => 'Viewed',
    'approved_date' => 'Approved',
    'refused_date' => 'Refused',
    'converted_date' => 'Converted',

    // Messages
    'messages' => [
        'created' => 'Estimate :number created as draft.',
        'updated' => 'Estimate :number updated.',
        'sent' => 'Estimate :number sent to :email.',
        'sent_success' => 'Estimate sent successfully.',
        'approved' => 'Estimate has been approved.',
        'approved_manually' => 'Estimate approved manually by admin.',
        'approved_by_customer' => 'Estimate approved by customer via portal.',
        'refused' => 'Estimate has been refused.',
        'refused_manually' => 'Estimate refused manually by admin.',
        'refused_by_customer' => 'Estimate refused by customer via portal.',
        'converted' => 'Estimate :number converted to invoice :invoice.',
        'converted_success' => 'Estimate converted to invoice :invoice successfully.',
        'duplicated' => 'Duplicated from estimate :number.',
        'duplicated_success' => 'Estimate duplicated successfully.',
        'expired' => 'Estimate has expired.',
        'not_editable' => 'This estimate can only be edited in Draft or Refused status.',
        'not_deletable' => 'Only draft estimates can be deleted.',
        'must_be_approved' => 'Estimate must be approved before converting to invoice.',
        'cannot_approve' => 'This estimate cannot be approved in its current status.',
        'cannot_refuse' => 'This estimate cannot be refused in its current status.',
        'viewed_by_customer' => 'Estimate viewed by customer.',
    ],

    // Portal
    'portal' => [
        'title' => 'Estimate',
        'from' => 'From',
        'to' => 'To',
        'approve_estimate' => 'Approve Estimate',
        'refuse_estimate' => 'Refuse Estimate',
        'refuse_reason' => 'Reason for refusing (optional)',
        'already_approved' => 'This estimate has been approved.',
        'already_refused' => 'This estimate has been refused.',
        'already_converted' => 'This estimate has been converted to an invoice.',
        'estimate_expired' => 'This estimate has expired.',
        'powered_by' => 'Powered by',
    ],

    // Notifications
    'notifications' => [
        'estimate_sent_subject' => 'Estimate :number from :company',
        'greeting' => 'Dear :name,',
        'estimate_sent_body' => ':company has sent you estimate :number for :amount.',
        'expires_on' => 'This estimate expires on :date.',
        'view_estimate' => 'View Estimate',
        'estimate_sent_footer' => 'Please review the estimate and let us know your decision.',
        'status_changed_subject' => 'Estimate :number — :status',
        'status_changed_greeting' => 'Estimate Status Update',
        'status_changed_body' => ':customer has :action estimate :number.',
        'reason' => 'Reason: :reason',
        'view_estimate_admin' => 'View Estimate',
    ],

    // Reports
    'report' => [
        'title' => 'Estimate Report',
        'total_estimates' => 'Total Estimates',
        'total_sent' => 'Total Sent',
        'total_approved' => 'Total Approved',
        'approval_rate' => 'Approval Rate',
        'conversion_rate' => 'Conversion Rate',
        'average_value' => 'Average Value',
        'total_value' => 'Total Value',
        'approved_value' => 'Approved Value',
    ],

    // Empty states
    'no_estimates' => 'No estimates found.',
    'no_history' => 'No history yet.',

    // Portal thank you
    'messages.thank_you_approved' => 'Thank you! The estimate has been approved.',
    'messages.thank_you_refused' => 'The estimate has been refused. Thank you for your response.',

    // Invoice link
    'converted_invoice' => 'Converted Invoice',
    'view_invoice' => 'View Invoice',
    'portal_link' => 'Portal Link',
    'copy_link' => 'Copy Link',
];
