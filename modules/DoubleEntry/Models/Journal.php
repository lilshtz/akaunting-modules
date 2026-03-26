<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use SoftDeletes;

    protected $table = 'double_entry_journals';

    protected $fillable = [
        'company_id',
        'date',
        'reference',
        'description',
        'basis',
        'status',
        'documentable_type',
        'documentable_id',
        'recurring_frequency',
        'next_recurring_date',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'next_recurring_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['date', 'reference', 'status'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo('App\Models\Auth\User', 'created_by');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeBasis($query, string $basis)
    {
        return $query->where('basis', $basis);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('recurring_frequency');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function getIsBalancedAttribute(): bool
    {
        return round($this->total_debit, 4) === round($this->total_credit, 4);
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('general.show'),
            'icon' => 'visibility',
            'url' => route('double-entry.journals.show', $this->id),
        ];

        if ($this->status === 'draft') {
            $actions[] = [
                'title' => trans('general.edit'),
                'icon' => 'edit',
                'url' => route('double-entry.journals.edit', $this->id),
            ];
        }

        $actions[] = [
            'title' => trans('general.duplicate'),
            'icon' => 'content_copy',
            'url' => route('double-entry.journals.duplicate', $this->id),
            'attributes' => ['data-method' => 'POST'],
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'double-entry.journals.destroy',
            'model' => $this,
        ];

        return $actions;
    }
}
