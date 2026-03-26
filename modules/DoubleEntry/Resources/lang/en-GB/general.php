<?php

return [
    'name' => 'Double-Entry',
    'description' => 'Double-entry accounting module',

    'accounts' => 'Account|Accounts',
    'chart_of_accounts' => 'Chart of Accounts',
    'journal_entries' => 'Journal Entries',
    'journal_entry' => 'Journal Entry',
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

    'reference' => 'Reference',
    'basis' => 'Basis',
    'accrual' => 'Accrual',
    'cash' => 'Cash',
    'debit' => 'Debit',
    'credit' => 'Credit',
    'account' => 'Account',
    'line_items' => 'Line Items',
    'add_line' => 'Add Line',
    'select_account' => 'Select Account',
    'balanced' => 'Balanced',
    'unbalanced' => 'Unbalanced',
    'created_by' => 'Created By',
    'reverse' => 'Reverse',
    'confirm_reverse' => 'Are you sure you want to reverse this journal entry?',
    'only_draft_editable' => 'Only draft journal entries can be edited.',
    'journal_reversed' => 'Journal entry has been reversed.',
    'journal_duplicated' => 'Journal entry has been duplicated as draft.',

    'recurring_frequency' => 'Recurring Frequency',
    'next_recurring_date' => 'Next Recurring Date',

    'statuses' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
    ],

    'frequencies' => [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ],

    'validation' => [
        'debit_or_credit' => 'A line cannot have both debit and credit amounts.',
        'line_amount_required' => 'Each line must have either a debit or credit amount.',
        'account_not_found' => 'The selected account does not belong to this company.',
        'unbalanced' => 'Total debits (:debit) must equal total credits (:credit).',
    ],

    'types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ],
];
