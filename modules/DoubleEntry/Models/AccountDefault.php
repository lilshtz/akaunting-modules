<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDefault extends Model
{
    protected $table = 'double_entry_account_defaults';

    protected $fillable = [
        'company_id',
        'type',
        'account_id',
    ];

    protected $sortable = ['type', 'created_at'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
