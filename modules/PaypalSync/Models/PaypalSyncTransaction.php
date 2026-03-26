<?php

namespace Modules\PaypalSync\Models;

use App\Abstracts\Model;

class PaypalSyncTransaction extends Model
{
    protected $table = 'paypal_sync_transactions';

    protected $fillable = [
        'paypal_transaction_id',
        'company_id',
        'bank_transaction_id',
        'amount',
        'currency',
        'date',
        'description',
        'payer_email',
        'status',
        'raw_json',
    ];

    protected $casts = [
        'amount' => 'double',
        'date' => 'date',
        'raw_json' => 'array',
    ];

    /**
     * Get the company that owns the transaction.
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    /**
     * Get the linked Akaunting bank transaction.
     */
    public function bankTransaction()
    {
        return $this->belongsTo('App\Models\Banking\Transaction', 'bank_transaction_id');
    }
}
