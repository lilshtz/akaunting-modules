<?php

namespace Modules\DoubleEntry\Database\Seeds;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DefaultAccounts
{
    public function run(): void
    {
        $companyId = 1;
        $now = Carbon::now();

        foreach ($this->accounts() as $account) {
            DB::table('double_entry_accounts')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'code' => $account['code'],
                ],
                [
                    'parent_id' => null,
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'description' => $account['description'] ?? null,
                    'opening_balance' => 0,
                    'enabled' => true,
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    'deleted_at' => null,
                ]
            );
        }
    }

    protected function accounts(): array
    {
        return [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'Checking Account', 'type' => 'asset'],
            ['code' => '1020', 'name' => 'Savings Account', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Prepaid Expenses', 'type' => 'asset'],
            ['code' => '1500', 'name' => 'Equipment', 'type' => 'asset'],
            ['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => 'asset'],
            ['code' => '1600', 'name' => 'Vehicles', 'type' => 'asset'],
            ['code' => '1610', 'name' => 'Accumulated Depreciation - Vehicles', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Credit Cards', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'Payroll Liabilities', 'type' => 'liability'],
            ['code' => '2300', 'name' => 'Sales Tax Payable', 'type' => 'liability'],
            ['code' => '2400', 'name' => 'Short-Term Loans', 'type' => 'liability'],
            ['code' => '2500', 'name' => 'Long-Term Loans', 'type' => 'liability'],
            ['code' => '2600', 'name' => 'Equipment Loans', 'type' => 'liability'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
            ['code' => '3100', 'name' => "Owner's Draw", 'type' => 'equity'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Construction Revenue', 'type' => 'income'],
            ['code' => '4010', 'name' => 'Owner Representative Fees', 'type' => 'income'],
            ['code' => '4020', 'name' => 'Reimbursement Income', 'type' => 'income'],
            ['code' => '4100', 'name' => 'Material Markup', 'type' => 'income'],
            ['code' => '4200', 'name' => 'Service Revenue', 'type' => 'income'],
            ['code' => '4900', 'name' => 'Other Income', 'type' => 'income'],
            ['code' => '5000', 'name' => 'COGS - Materials', 'type' => 'expense'],
            ['code' => '5010', 'name' => 'COGS - Labor', 'type' => 'expense'],
            ['code' => '5020', 'name' => 'Subcontractor Costs', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Advertising & Marketing', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Auto & Truck', 'type' => 'expense'],
            ['code' => '5210', 'name' => 'Fuel', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Insurance', 'type' => 'expense'],
            ['code' => '5310', 'name' => 'Workers Comp Insurance', 'type' => 'expense'],
            ['code' => '5400', 'name' => 'Office Supplies', 'type' => 'expense'],
            ['code' => '5500', 'name' => 'Professional Fees', 'type' => 'expense'],
            ['code' => '5600', 'name' => 'Rent/Lease', 'type' => 'expense'],
            ['code' => '5700', 'name' => 'Repairs & Maintenance', 'type' => 'expense'],
            ['code' => '5800', 'name' => 'Tools & Small Equipment', 'type' => 'expense'],
            ['code' => '5900', 'name' => 'Utilities', 'type' => 'expense'],
            ['code' => '5950', 'name' => 'Permits & Licenses', 'type' => 'expense'],
            ['code' => '5960', 'name' => 'Bonding', 'type' => 'expense'],
            ['code' => '5999', 'name' => 'Miscellaneous Expense', 'type' => 'expense'],
        ];
    }
}
