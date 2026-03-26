<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;

class BankFeedReconciliation extends Model
{
    protected $table = 'bank_feed_reconciliations';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'statement_start_date',
        'statement_end_date',
        'opening_balance',
        'closing_balance',
        'reconciled_balance',
        'difference',
        'status',
        'matched_transaction_ids',
        'completed_at',
    ];

    protected $casts = [
        'opening_balance' => 'double',
        'closing_balance' => 'double',
        'reconciled_balance' => 'double',
        'difference' => 'double',
        'matched_transaction_ids' => 'json',
        'statement_start_date' => 'date',
        'statement_end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $sortable = ['statement_start_date', 'statement_end_date', 'status', 'created_at'];

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function bankFeedTransactions()
    {
        return $this->hasMany(BankFeedTransaction::class, 'reconciliation_id');
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('bank_account_id', $accountId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function getIsCompleteAttribute(): bool
    {
        return abs($this->difference) < 0.01;
    }
}
