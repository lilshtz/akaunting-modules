<?php

namespace Modules\DoubleEntry\Database\Seeds;

use Illuminate\Database\Seeder;
use Modules\DoubleEntry\Models\Account;

class DefaultAccounts extends Seeder
{
    public function run(): void
    {
        $companyId = session('company_id') ?? company_id();

        $accounts = [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Prepaid Expenses', 'type' => 'asset'],
            ['code' => '1500', 'name' => 'Fixed Assets', 'type' => 'asset'],
            ['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Accrued Liabilities', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'Notes Payable', 'type' => 'liability'],
            ['code' => '2300', 'name' => 'Sales Tax Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity'],
            ['code' => '3200', 'name' => "Owner's Draw", 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income'],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'income'],
            ['code' => '4200', 'name' => 'Other Income', 'type' => 'income'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Materials', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Labor', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Subcontractor Costs', 'type' => 'expense'],
            ['code' => '6000', 'name' => 'Rent Expense', 'type' => 'expense'],
            ['code' => '6100', 'name' => 'Utilities', 'type' => 'expense'],
            ['code' => '6200', 'name' => 'Insurance', 'type' => 'expense'],
            ['code' => '6300', 'name' => 'Office Supplies', 'type' => 'expense'],
            ['code' => '6400', 'name' => 'Vehicle Expense', 'type' => 'expense'],
            ['code' => '6500', 'name' => 'Tools & Equipment', 'type' => 'expense'],
            ['code' => '6600', 'name' => 'Professional Fees', 'type' => 'expense'],
            ['code' => '6700', 'name' => 'Advertising', 'type' => 'expense'],
            ['code' => '6800', 'name' => 'Depreciation', 'type' => 'expense'],
            ['code' => '6900', 'name' => 'Miscellaneous Expense', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'code' => $account['code'],
                ],
                array_merge($account, [
                    'company_id' => $companyId,
                    'enabled' => true,
                ])
            );
        }
    }
}
