<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;

class BankFeedImport extends Model
{
    protected $table = 'bank_feed_imports';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'filename',
        'format',
        'row_count',
        'status',
        'column_mapping',
        'imported_at',
    ];

    protected $casts = [
        'column_mapping' => 'json',
        'imported_at' => 'datetime',
        'row_count' => 'integer',
    ];

    protected $sortable = ['filename', 'format', 'row_count', 'status', 'imported_at', 'created_at'];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    public const FORMAT_CSV = 'csv';
    public const FORMAT_OFX = 'ofx';
    public const FORMAT_QFX = 'qfx';

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function transactions()
    {
        return $this->hasMany(BankFeedTransaction::class, 'import_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('bank-feeds::general.view_transactions'),
            'icon' => 'list',
            'url' => route('bank-feeds.transactions.index', ['import_id' => $this->id]),
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'bank-feeds.imports.destroy',
            'model' => $this,
        ];

        return $actions;
    }
}
