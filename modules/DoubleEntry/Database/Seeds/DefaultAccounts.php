<?php

namespace Modules\DoubleEntry\Database\Seeds;

use Illuminate\Database\Seeder;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountDefault;

class DefaultAccounts extends Seeder
{
    /**
     * Seed the default Chart of Accounts.
     *
     * @param  int  $companyId
     * @return void
     */
    public function run($companyId = 1)
    {
        $accounts = [
            // Assets (1000-1999)
            ['type' => 'asset', 'code' => '1000', 'name' => 'Cash on Hand'],
            ['type' => 'asset', 'code' => '1010', 'name' => 'Bank (Current Account)', 'default' => 'bank_current'],
            ['type' => 'asset', 'code' => '1020', 'name' => 'Bank (Savings Account)'],
            ['type' => 'asset', 'code' => '1100', 'name' => 'Accounts Receivable', 'default' => 'accounts_receivable'],
            ['type' => 'asset', 'code' => '1150', 'name' => 'Undeposited Funds', 'default' => 'undeposited_funds'],
            ['type' => 'asset', 'code' => '1200', 'name' => 'Inventory'],
            ['type' => 'asset', 'code' => '1300', 'name' => 'Prepaid Expenses'],
            ['type' => 'asset', 'code' => '1500', 'name' => 'Fixed Assets'],
            ['type' => 'asset', 'code' => '1510', 'name' => 'Accumulated Depreciation'],

            // Liabilities (2000-2999)
            ['type' => 'liability', 'code' => '2000', 'name' => 'Accounts Payable', 'default' => 'accounts_payable'],
            ['type' => 'liability', 'code' => '2100', 'name' => 'Credit Card'],
            ['type' => 'liability', 'code' => '2200', 'name' => 'Sales Tax Payable', 'default' => 'sales_tax'],
            ['type' => 'liability', 'code' => '2210', 'name' => 'Purchase Tax Payable', 'default' => 'purchase_tax'],
            ['type' => 'liability', 'code' => '2300', 'name' => 'Accrued Liabilities'],
            ['type' => 'liability', 'code' => '2500', 'name' => 'Long-Term Debt'],

            // Equity (3000-3999)
            ['type' => 'equity', 'code' => '3000', 'name' => "Owner's Equity", 'default' => 'owners_equity'],
            ['type' => 'equity', 'code' => '3100', 'name' => 'Retained Earnings', 'default' => 'retained_earnings'],
            ['type' => 'equity', 'code' => '3200', 'name' => 'Owner Draws'],

            // Income (4000-4999)
            ['type' => 'income', 'code' => '4000', 'name' => 'Sales Income', 'default' => 'sales_income'],
            ['type' => 'income', 'code' => '4100', 'name' => 'Service Revenue'],
            ['type' => 'income', 'code' => '4200', 'name' => 'Interest Income'],
            ['type' => 'income', 'code' => '4900', 'name' => 'Other Income'],

            // Expenses (5000-5999)
            ['type' => 'expense', 'code' => '5000', 'name' => 'Cost of Sales', 'default' => 'cost_of_sales'],
            ['type' => 'expense', 'code' => '5100', 'name' => 'Advertising & Marketing'],
            ['type' => 'expense', 'code' => '5200', 'name' => 'Bank Fees & Charges'],
            ['type' => 'expense', 'code' => '5300', 'name' => 'Insurance'],
            ['type' => 'expense', 'code' => '5400', 'name' => 'Office Supplies'],
            ['type' => 'expense', 'code' => '5500', 'name' => 'Rent'],
            ['type' => 'expense', 'code' => '5600', 'name' => 'Salaries & Wages'],
            ['type' => 'expense', 'code' => '5700', 'name' => 'Utilities'],
            ['type' => 'expense', 'code' => '5800', 'name' => 'Depreciation'],
            ['type' => 'expense', 'code' => '5900', 'name' => 'Other Expenses'],
        ];

        foreach ($accounts as $data) {
            $defaultType = $data['default'] ?? null;
            unset($data['default']);

            $account = Account::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'code' => $data['code'],
                ],
                array_merge($data, [
                    'company_id' => $companyId,
                    'enabled' => true,
                    'opening_balance' => 0,
                ])
            );

            if ($defaultType) {
                AccountDefault::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'type' => $defaultType,
                    ],
                    [
                        'account_id' => $account->id,
                    ]
                );
            }
        }
    }
}
