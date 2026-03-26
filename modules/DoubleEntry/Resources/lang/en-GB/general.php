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

    'journal_lines' => 'Journal Lines',

    'bases' => [
        'accrual' => 'Accrual',
        'cash' => 'Cash',
    ],

    'running_balance' => 'Running Balance',
    'as_of_date' => 'As of Date',
    'date_from' => 'Date From',
    'date_to' => 'Date To',
    'all_accounts' => 'All Accounts',
    'export_csv' => 'Export CSV',
    'export_pdf' => 'Export PDF',
    'grand_total' => 'Grand Total',
    'net_movement' => 'Net Movement',
    'opening' => 'Opening',
    'closing' => 'Closing',
    'total_debits' => 'Total Debits',
    'total_credits' => 'Total Credits',
    'no_transactions' => 'No transactions found for this period.',

    'total_assets' => 'Total Assets',
    'total_liabilities' => 'Total Liabilities',
    'total_equity' => 'Total Equity',
    'total_liabilities_equity' => 'Total Liabilities & Equity',
    'assets_equal_liabilities_equity' => 'Assets = Liabilities + Equity',
    'comparative' => 'Comparative',
    'current_period' => 'Current Period',
    'prior_period' => 'Prior Period',

    'gross_profit' => 'Gross Profit',
    'total_income' => 'Total Income',
    'total_expenses' => 'Total Expenses',
    'net_profit' => 'Net Profit',
    'net_loss' => 'Net Loss',
    'net_income' => 'Net Income',
    'percentage_of_income' => '% of Income',
    'period_comparison' => 'Period Comparison',
    'period_breakdown' => 'Period Breakdown',
    'no_breakdown' => 'No Breakdown',
    'cost_of_goods_sold' => 'Cost of Goods Sold',

    'types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ],
];
