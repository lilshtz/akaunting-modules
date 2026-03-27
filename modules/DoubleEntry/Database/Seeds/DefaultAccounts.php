<?php

namespace Modules\DoubleEntry\Database\Seeds;

use Illuminate\Database\Seeder;
use Modules\DoubleEntry\Models\Account;

class DefaultAccounts extends Seeder
{
    public static function definitions(): array
    {
        return [
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => Account::TYPE_ASSET, 'detail_type' => 'Current Asset'],
            ['code' => '1100', 'name' => 'Bank Account', 'type' => Account::TYPE_ASSET, 'detail_type' => 'Current Asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET, 'detail_type' => 'Receivable'],
            ['code' => '1500', 'name' => 'Inventory', 'type' => Account::TYPE_ASSET, 'detail_type' => 'Current Asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => Account::TYPE_LIABILITY, 'detail_type' => 'Current Liability'],
            ['code' => '2100', 'name' => 'Sales Tax Payable', 'type' => Account::TYPE_LIABILITY, 'detail_type' => 'Current Liability'],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => Account::TYPE_EQUITY, 'detail_type' => 'Equity'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => Account::TYPE_EQUITY, 'detail_type' => 'Equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => Account::TYPE_INCOME, 'detail_type' => 'Operating Revenue'],
            ['code' => '4500', 'name' => 'Other Income', 'type' => Account::TYPE_INCOME, 'detail_type' => 'Other Income'],
            ['code' => '5000', 'name' => 'Purchases', 'type' => Account::TYPE_EXPENSE, 'detail_type' => 'Cost of Goods Sold'],
            ['code' => '5200', 'name' => 'Bank Charges', 'type' => Account::TYPE_EXPENSE, 'detail_type' => 'Operating Expense'],
            ['code' => '5300', 'name' => 'General Expenses', 'type' => Account::TYPE_EXPENSE, 'detail_type' => 'Operating Expense'],
        ];
    }

    public function run(): void
    {
        $companyId = company_id();

        foreach (static::definitions() as $definition) {
            Account::firstOrCreate(
                ['company_id' => $companyId, 'code' => $definition['code']],
                array_merge($definition, [
                    'company_id' => $companyId,
                    'opening_balance' => 0,
                    'enabled' => true,
                    'created_by' => auth()->id(),
                ])
            );
        }
    }
}
