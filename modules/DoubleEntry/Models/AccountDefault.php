<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class AccountDefault extends Model
{
    protected $table = 'double_entry_account_defaults';

    protected $fillable = [
        'company_id',
        'type',
        'account_id',
    ];

    public $timestamps = false;

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the available default types.
     */
    public static function getTypes(): array
    {
        return [
            'bank_current' => 'Bank (Current Account)',
            'accounts_receivable' => 'Accounts Receivable',
            'accounts_payable' => 'Accounts Payable',
            'sales_income' => 'Sales Income',
            'cost_of_sales' => 'Cost of Sales',
            'sales_tax' => 'Sales Tax',
            'purchase_tax' => 'Purchase Tax',
            'retained_earnings' => 'Retained Earnings',
            'owners_equity' => "Owner's Equity",
            'undeposited_funds' => 'Undeposited Funds',
        ];
    }
}
