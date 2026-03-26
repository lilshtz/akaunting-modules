<?php

return [
    'name' => 'Sales & Purchase Orders',
    'sales_orders' => 'Sales Orders',
    'sales_order' => 'Sales Order',
    'purchase_orders' => 'Purchase Orders',
    'purchase_order' => 'Purchase Order',
    'order_summary' => 'Order Summary',
    'settings' => 'Order Settings',
    'new_sales_order' => 'New Sales Order',
    'edit_sales_order' => 'Edit Sales Order',
    'new_purchase_order' => 'New Purchase Order',
    'edit_purchase_order' => 'Edit Purchase Order',
    'order_details' => 'Order Details',
    'order_number' => 'Order Number',
    'order_date' => 'Order Date',
    'delivery_date' => 'Delivery Date',
    'customer' => 'Customer',
    'vendor' => 'Vendor',
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

    // SO Statuses
    'so_statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'confirmed' => 'Confirmed',
        'issued' => 'Issued',
        'cancelled' => 'Cancelled',
    ],

    // PO Statuses
    'po_statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'confirmed' => 'Confirmed',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ],

    // Actions
    'actions' => [
        'send' => 'Send',
        'send_to_customer' => 'Send to Customer',
        'send_to_vendor' => 'Send to Vendor',
        'confirm' => 'Confirm',
        'issue' => 'Mark as Issued',
        'receive' => 'Mark as Received',
        'cancel' => 'Cancel',
        'convert_to_invoice' => 'Convert to Invoice',
        'convert_to_bill' => 'Convert to Bill',
        'convert_to_po' => 'Convert to Purchase Order',
        'duplicate' => 'Duplicate',
        'download_pdf' => 'Download PDF',
        'import' => 'Import',
        'export' => 'Export',
    ],

    // Settings
    'so_prefix' => 'Sales Order Prefix',
    'so_next_number' => 'Sales Order Next Number',
    'po_prefix' => 'Purchase Order Prefix',
    'po_next_number' => 'Purchase Order Next Number',
    'default_terms' => 'Default Terms',
    'template' => 'Template',

    // Timeline / History
    'timeline' => 'Timeline',
    'history' => 'History',

    // Messages
    'messages' => [
        'created' => 'Order :number created as draft.',
        'updated' => 'Order :number updated.',
        'sent' => 'Order :number sent to :email.',
        'sent_success' => 'Order sent successfully.',
        'confirmed' => 'Order has been confirmed.',
        'confirmed_success' => 'Order confirmed successfully.',
        'issued' => 'Sales order has been marked as issued.',
        'issued_success' => 'Sales order marked as issued successfully.',
        'received' => 'Purchase order has been marked as received.',
        'received_success' => 'Purchase order marked as received successfully.',
        'cancelled' => 'Order has been cancelled.',
        'cancelled_success' => 'Order cancelled successfully.',
        'converted_to_invoice' => 'Sales order :number converted to invoice :invoice.',
        'converted_invoice_success' => 'Sales order converted to invoice :invoice successfully.',
        'converted_to_bill' => 'Purchase order :number converted to bill :bill.',
        'converted_bill_success' => 'Purchase order converted to bill :bill successfully.',
        'converted_to_po' => 'Sales order :number converted to purchase order :po.',
        'converted_po_success' => 'Sales order converted to purchase order :po successfully.',
        'po_created_from_so' => 'Purchase order :number created from sales order :so.',
        'duplicated' => 'Duplicated from order :number.',
        'duplicated_success' => 'Order duplicated successfully.',
        'not_editable' => 'This order can only be edited in Draft status.',
        'not_deletable' => 'Only draft orders can be deleted.',
        'must_be_confirmed' => 'Order must be confirmed before conversion.',
        'must_be_confirmed_or_issued' => 'Sales order must be confirmed or issued before converting to invoice.',
        'must_be_confirmed_or_received' => 'Purchase order must be confirmed or received before converting to bill.',
        'import_success' => ':count orders imported successfully.',
        'import_failed' => 'Failed to read import file.',
        'import_row_invalid' => 'Row :line has insufficient data.',
        'import_contact_not_found' => 'Row :line: contact ":name" not found.',
        'import_invalid_values' => 'Row :line has invalid quantity or price values.',
        'imported' => 'Order :number imported.',
        'imported_success' => ':count orders imported successfully.',
        'export_success' => 'Orders exported successfully.',
        'stock_updated' => 'Stock levels updated for :count items.',
        'estimate_converted' => 'Estimate :number converted to sales order :so.',
        'purchase_order_created' => 'Purchase order :number created as draft.',
        'purchase_order_updated' => 'Purchase order :number updated.',
        'purchase_order_sent' => 'Purchase order :number sent to :email.',
        'purchase_order_confirmed' => 'Purchase order :number confirmed.',
        'purchase_order_received' => 'Purchase order :number marked as received.',
        'purchase_order_cancelled' => 'Purchase order :number cancelled.',
        'purchase_order_duplicated' => 'Duplicated from purchase order :number.',
    ],

    // From / To labels
    'from' => 'From',
    'to' => 'To',

    // Notifications
    'notifications' => [
        'order_sent_subject' => ':type :number from :company',
        'greeting' => 'Dear :name,',
        'order_sent_body' => ':company has sent you :type :number for :amount.',
        'delivery_date' => 'Expected delivery: :date.',
        'view_order' => 'View Order',
        'order_sent_footer' => 'Please review the order and confirm.',
    ],

    // Reports
    'report' => [
        'sales_title' => 'Sales Order Report',
        'purchase_title' => 'Purchase Order Report',
        'total_orders' => 'Total Orders',
        'total_confirmed' => 'Total Confirmed',
        'total_value' => 'Total Value',
        'confirmed_value' => 'Confirmed Value',
        'average_value' => 'Average Value',
        'by_customer' => 'By Customer',
        'by_vendor' => 'By Vendor',
        'customer_name' => 'Customer/Vendor',
        'order_count' => 'Orders',
        'total_amount' => 'Total Amount',
    ],

    // Empty states
    'no_sales_orders' => 'No sales orders found.',
    'no_purchase_orders' => 'No purchase orders found.',
    'no_history' => 'No history yet.',

    // Converted documents
    'converted_invoice' => 'Converted Invoice',
    'converted_bill' => 'Converted Bill',
    'converted_purchase_orders' => 'Converted Purchase Orders',
    'parent_sales_order' => 'Parent Sales Order',
    'view_invoice' => 'View Invoice',
    'view_bill' => 'View Bill',

    // Integration
    'convert_estimate' => 'Convert to Sales Order',
    'from_estimate' => 'From Estimate',

    // Import
    'import_file' => 'Import File',
    'import_orders' => 'Import Orders',
    'select_csv' => 'Select CSV File',
];
