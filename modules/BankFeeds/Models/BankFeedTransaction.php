<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;

class BankFeedTransaction extends Model
{
    protected $table = 'bank_feed_transactions';

    protected $fillable = [
        'import_id',
        'bank_account_id',
        'date',
        'description',
        'amount',
        'type',
        'raw_data_json',
        'category_id',
        'vendor_id',
        'matched_transaction_id',
        'status',
        'duplicate_hash',
        'is_duplicate',
        'match_confidence',
        'reconciliation_id',
    ];

    protected $casts = [
        'amount' => 'double',
        'raw_data_json' => 'json',
        'date' => 'date',
        'is_duplicate' => 'boolean',
        'match_confidence' => 'double',
    ];

    protected $sortable = ['date', 'description', 'amount', 'type', 'status', 'created_at'];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CATEGORIZED = 'categorized';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_IGNORED = 'ignored';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';

    public function import()
    {
        return $this->belongsTo(BankFeedImport::class, 'import_id');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Setting\Category');
    }

    public function matchedTransaction()
    {
        return $this->belongsTo('App\Models\Banking\Transaction', 'matched_transaction_id');
    }

    public function reconciliation()
    {
        return $this->belongsTo(BankFeedReconciliation::class, 'reconciliation_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUncategorized($query)
    {
        return $query->where('status', self::STATUS_PENDING)->whereNull('category_id');
    }

    public function scopeUnmatched($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CATEGORIZED]);
    }

    public function scopeMatched($query)
    {
        return $query->where('status', self::STATUS_MATCHED);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('bank_account_id', $accountId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->type === self::TYPE_WITHDRAWAL ? '-' : '+';
        return $prefix . money(abs($this->amount), setting('default.currency', 'USD'));
    }

    public function getSignedAmountAttribute(): float
    {
        return $this->type === self::TYPE_WITHDRAWAL ? -abs($this->amount) : abs($this->amount);
    }

    public function getDuplicateHashValue(): string
    {
        return hash('sha256', implode('|', [
            $this->bank_account_id,
            $this->date->format('Y-m-d'),
            number_format($this->amount, 4, '.', ''),
            strtolower(trim($this->description)),
        ]));
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        if ($this->status === self::STATUS_PENDING) {
            $actions[] = [
                'title' => trans('bank-feeds::general.ignore'),
                'icon' => 'visibility_off',
                'url' => route('bank-feeds.transactions.ignore', $this->id),
            ];
        }

        if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_CATEGORIZED])) {
            $actions[] = [
                'title' => trans('bank-feeds::general.matching.find_match'),
                'icon' => 'compare_arrows',
                'url' => route('bank-feeds.matching.show', $this->id),
            ];
        }

        return $actions;
    }
}
