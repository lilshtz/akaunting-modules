<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class AccountDefault extends Model
{
    protected $table = 'double_entry_account_defaults';

    protected $fillable = [
        'company_id',
        'key',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
