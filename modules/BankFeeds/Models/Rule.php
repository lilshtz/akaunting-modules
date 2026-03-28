<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DoubleEntry\Models\Account;

class Rule extends Model
{
    protected $table = 'bank_feed_rules';

    protected $fillable = [
        'company_id',
        'name',
        'field',
        'operator',
        'value',
        'value_end',
        'category_id',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'category_id');
    }
}
