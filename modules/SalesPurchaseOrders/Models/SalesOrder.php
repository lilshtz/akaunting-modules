<?php

namespace Modules\SalesPurchaseOrders\Models;

use App\Models\Document\Document;
use Illuminate\Database\Eloquent\Builder;

class SalesOrder extends Document
{
    public const SALES_ORDER_TYPE = 'sales-order';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_CONFIRMED,
        self::STATUS_ISSUED,
        self::STATUS_CANCELLED,
    ];

    protected static function booted()
    {
        static::addGlobalScope('sales-order', function (Builder $builder) {
            $builder->where('documents.type', self::SALES_ORDER_TYPE);
        });

        static::creating(function ($model) {
            $model->type = self::SALES_ORDER_TYPE;
        });
    }

    public function orderHistories()
    {
        return $this->hasMany(OrderHistory::class, 'document_id');
    }

    public function convertedInvoice()
    {
        return $this->hasOne(Document::class, 'parent_id')
            ->where('type', Document::INVOICE_TYPE);
    }

    public function convertedPurchaseOrders()
    {
        return $this->hasMany(Document::class, 'parent_id')
            ->where('type', PurchaseOrder::PURCHASE_ORDER_TYPE);
    }

    public function scopeSalesOrder(Builder $query): Builder
    {
        return $query;
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'status-draft',
            self::STATUS_SENT => 'status-danger',
            self::STATUS_CONFIRMED => 'status-success',
            self::STATUS_ISSUED => 'status-partial',
            self::STATUS_CANCELLED => 'status-canceled',
            default => 'status-draft',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SENT => 'bg-blue-100 text-blue-800',
            self::STATUS_CONFIRMED => 'bg-green-100 text-green-800',
            self::STATUS_ISSUED => 'bg-purple-100 text-purple-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return trans('sales-purchase-orders::general.so_statuses.' . $this->status);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
