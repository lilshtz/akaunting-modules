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
    ];

    protected $casts = [
        'amount' => 'double',
        'raw_data_json' => 'json',
        'date' => 'date',
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

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->type === self::TYPE_WITHDRAWAL ? '-' : '+';
        return $prefix . money(abs($this->amount), setting('default.currency', 'USD'));
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

        return $actions;
    }
}
