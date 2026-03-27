<?php

return [
    'name' => 'Double-Entry',
    'description' => 'Double-entry accounting with chart of accounts, journal entries, and financial reports',

    'chart_of_accounts' => 'Chart of Accounts',
    'journal_entries' => 'Journal Entries',
    'journal_entry' => 'Journal Entry',
    'account_defaults' => 'Account Defaults',
    'general_ledger' => 'General Ledger',
    'trial_balance' => 'Trial Balance',
    'balance_sheet' => 'Balance Sheet',
    'profit_loss' => 'Profit & Loss',

    'account' => 'Account',
    'accounts' => 'Accounts',
    'code' => 'Code',
    'opening_balance' => 'Opening Balance',
    'debit' => 'Debit',
    'credit' => 'Credit',
    'balance' => 'Balance',
    'date' => 'Date',
    'number' => 'Number',
    'reference' => 'Reference',
    'status' => 'Status',
    'description' => 'Description',
    'parent_account' => 'Parent Account',
    'account_type' => 'Account Type',
    'running_balance' => 'Running Balance',
    'net_profit' => 'Net Profit',
    'total_assets' => 'Total Assets',
    'total_liabilities' => 'Total Liabilities',
    'total_equity' => 'Total Equity',
    'total_income' => 'Total Income',
    'total_expenses' => 'Total Expenses',
    'import_accounts' => 'Import Accounts',
    'add_line' => 'Add Line',
    'remove_line' => 'Remove Line',

    'types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'voided' => 'Voided',
    ],

    'imported' => ':count accounts imported successfully',

    'error' => [
        'has_transactions' => 'This account has journal entries and cannot be deleted.',
        'not_balanced' => 'Journal entry debits and credits must balance.',
        'posted_journal' => 'Posted journal entries cannot be deleted. Void them instead.',
    ],
];
