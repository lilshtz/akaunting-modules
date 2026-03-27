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
            'bank_current' => trans('double-entry::general.defaults.bank_current'),
            'accounts_receivable' => trans('double-entry::general.defaults.accounts_receivable'),
            'accounts_payable' => trans('double-entry::general.defaults.accounts_payable'),
            'sales_income' => trans('double-entry::general.defaults.sales_income'),
            'cost_of_sales' => trans('double-entry::general.defaults.cost_of_sales'),
            'sales_tax' => trans('double-entry::general.defaults.sales_tax'),
            'purchase_tax' => trans('double-entry::general.defaults.purchase_tax'),
            'retained_earnings' => trans('double-entry::general.defaults.retained_earnings'),
            'owners_equity' => trans('double-entry::general.defaults.owners_equity'),
            'undeposited_funds' => trans('double-entry::general.defaults.undeposited_funds'),
        ];
    }
}
