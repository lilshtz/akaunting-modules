<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;

class Transaction extends Model
{
    protected $table = 'bank_feed_transactions';

    protected $fillable = [
        'company_id',
        'import_id',
        'bank_account_id',
        'date',
        'description',
        'amount',
        'type',
        'raw_data_json',
        'category_id',
        'matched_journal_id',
        'status',
        'duplicate_hash',
        'is_duplicate',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:4',
        'raw_data_json' => 'array',
        'is_duplicate' => 'boolean',
    ];

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'category_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function matchedJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'matched_journal_id');
    }
}
