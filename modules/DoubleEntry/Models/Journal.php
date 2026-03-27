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
        'number',
        'date',
        'description',
        'reference',
        'status',
        'is_recurring',
        'recurring_frequency',
        'next_run_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'is_recurring' => 'boolean',
        'next_run_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    /**
     * Scope to posted journals.
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope to draft journals.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if the journal is balanced.
     */
    public function getIsBalancedAttribute(): bool
    {
        $totalDebits = $this->lines->sum('debit');
        $totalCredits = $this->lines->sum('credit');

        return bccomp($totalDebits, $totalCredits, 4) === 0;
    }

    /**
     * Get total debits.
     */
    public function getTotalDebitsAttribute()
    {
        return $this->lines->sum('debit');
    }

    /**
     * Get total credits.
     */
    public function getTotalCreditsAttribute()
    {
        return $this->lines->sum('credit');
    }
}
