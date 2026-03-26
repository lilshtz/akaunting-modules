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

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
