<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DoubleEntry\Models\Account;

class Import extends Model
{
    protected $table = 'bank_feed_imports';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'filename',
        'original_filename',
        'format',
        'row_count',
        'status',
        'column_mapping',
        'error_message',
        'imported_at',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'imported_at' => 'datetime',
    ];

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'import_id');
    }
}
