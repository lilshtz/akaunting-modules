<?php

return [
    'name' => 'Double-Entry',
    'description' => 'Double-entry accounting module',

    'accounts' => 'Account|Accounts',
    'chart_of_accounts' => 'Chart of Accounts',
    'journal_entries' => 'Journal Entries',
    'general_ledger' => 'General Ledger',
    'trial_balance' => 'Trial Balance',
    'balance_sheet' => 'Balance Sheet',
    'profit_loss' => 'Profit & Loss',

    'account_code' => 'Account Code',
    'account_name' => 'Account Name',
    'account_type' => 'Account Type',
    'parent_account' => 'Parent Account',
    'opening_balance' => 'Opening Balance',

    'import_accounts' => 'Import Accounts',
    'csv_file' => 'CSV File',
    'csv_format' => 'CSV Format: code, name, type, parent_code, description, opening_balance',

    'types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ],
];
