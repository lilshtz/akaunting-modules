<?php

namespace Modules\CreditDebitNotes\Models;

use App\Models\Document\Document;
use Illuminate\Database\Eloquent\Builder;

class CreditNote extends Document
{
    public const NOTE_TYPE = 'credit-note';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_OPEN = 'open';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_OPEN,
        self::STATUS_PARTIAL,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected static function booted()
    {
        static::addGlobalScope('credit-note', function (Builder $builder) {
            $builder->where('documents.type', self::NOTE_TYPE);
        });

        static::creating(function ($model) {
            $model->type = self::NOTE_TYPE;
        });
    }

    public function histories()
    {
        return $this->hasMany(NoteHistory::class, 'document_id');
    }

    public function portalToken()
    {
        return $this->hasOne(NotePortalToken::class, 'document_id');
    }

    public function linkedInvoice()
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function applications()
    {
        return $this->hasMany(CreditNoteApplication::class, 'credit_note_id');
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SENT => 'bg-blue-100 text-blue-800',
            self::STATUS_OPEN => 'bg-green-100 text-green-800',
            self::STATUS_PARTIAL => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CLOSED => 'bg-purple-100 text-purple-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return trans('credit-debit-notes::general.statuses.' . $this->status);
    }

    public function getAvailableAmountAttribute(): float
    {
        return $this->amount - $this->applications->sum('amount');
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
