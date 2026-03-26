<?php

namespace Modules\Receipts\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $table = 'receipts';

    protected $fillable = [
        'company_id',
        'image_path',
        'thumbnail_path',
        'ocr_raw_json',
        'vendor_name',
        'receipt_date',
        'amount',
        'tax_amount',
        'currency',
        'category_id',
        'status',
        'transaction_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'double',
        'tax_amount' => 'double',
        'ocr_raw_json' => 'json',
        'receipt_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['vendor_name', 'receipt_date', 'amount', 'status', 'created_at'];

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_MATCHED = 'matched';

    public const STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_REVIEWED,
        self::STATUS_PROCESSED,
        self::STATUS_MATCHED,
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Setting\Category');
    }

    public function creator()
    {
        return $this->belongsTo('App\Models\Auth\User', 'created_by');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_UPLOADED, self::STATUS_REVIEWED]);
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        if ($this->status === self::STATUS_UPLOADED || $this->status === self::STATUS_REVIEWED) {
            $actions[] = [
                'title' => trans('receipts::general.review'),
                'icon' => 'visibility',
                'url' => route('receipts.receipts.review', $this->id),
            ];
        }

        if ($this->status === self::STATUS_REVIEWED) {
            $actions[] = [
                'title' => trans('receipts::general.process'),
                'icon' => 'receipt',
                'url' => route('receipts.receipts.process', $this->id),
            ];
        }

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'receipts.receipts.destroy',
            'model' => $this,
        ];

        return $actions;
    }

    public function getFormattedAmountAttribute(): string
    {
        if ($this->amount === null) {
            return '-';
        }

        return money($this->amount, $this->currency ?? setting('default.currency', 'USD'));
    }
}
