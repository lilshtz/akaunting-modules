<?php

namespace Modules\Estimates\Models;

use App\Models\Document\Document;
use Illuminate\Database\Eloquent\Builder;

class Estimate extends Document
{
    public const ESTIMATE_TYPE = 'estimate';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_VIEWED,
        self::STATUS_APPROVED,
        self::STATUS_REFUSED,
        self::STATUS_CONVERTED,
        self::STATUS_EXPIRED,
    ];

    protected static function booted()
    {
        static::addGlobalScope('estimate', function (Builder $builder) {
            $builder->where('documents.type', self::ESTIMATE_TYPE);
        });

        static::creating(function ($model) {
            $model->type = self::ESTIMATE_TYPE;
        });
    }

    public function estimateHistories()
    {
        return $this->hasMany(EstimateHistory::class, 'document_id');
    }

    public function portalToken()
    {
        return $this->hasOne(EstimatePortalToken::class, 'document_id');
    }

    public function convertedInvoice()
    {
        return $this->hasOne(Document::class, 'parent_id')
            ->where('type', Document::INVOICE_TYPE);
    }

    public function scopeEstimate(Builder $query): Builder
    {
        return $query;
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'status-draft',
            self::STATUS_SENT => 'status-danger',
            self::STATUS_VIEWED => 'status-sent',
            self::STATUS_APPROVED => 'status-success',
            self::STATUS_REFUSED => 'status-canceled',
            self::STATUS_CONVERTED => 'status-partial',
            self::STATUS_EXPIRED => 'status-canceled',
            default => 'status-draft',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SENT => 'bg-blue-100 text-blue-800',
            self::STATUS_VIEWED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REFUSED => 'bg-red-100 text-red-800',
            self::STATUS_CONVERTED => 'bg-purple-100 text-purple-800',
            self::STATUS_EXPIRED => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return trans('estimates::general.statuses.' . $this->status);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REFUSED]);
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isExpired(): bool
    {
        return $this->due_at && $this->due_at->isPast()
            && ! in_array($this->status, [self::STATUS_APPROVED, self::STATUS_CONVERTED]);
    }

    public function checkExpiry(): void
    {
        if ($this->isExpired() && $this->status !== self::STATUS_EXPIRED) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            EstimateHistory::create([
                'company_id' => $this->company_id,
                'document_id' => $this->id,
                'status' => self::STATUS_EXPIRED,
                'description' => trans('estimates::general.messages.expired'),
            ]);
        }
    }
}
