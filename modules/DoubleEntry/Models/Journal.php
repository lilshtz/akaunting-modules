<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    protected $table = 'double_entry_journals';

    protected $fillable = [
        'company_id',
        'number',
        'date',
        'status',
        'reference',
        'source_type',
        'source_id',
        'description',
        'total_debit',
        'total_credit',
        'is_recurring',
        'recurring_frequency',
        'next_run_at',
        'posted_at',
        'voided_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_debit' => 'double',
        'total_credit' => 'double',
        'is_recurring' => 'boolean',
        'next_run_at' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'journal_id')->orderBy('line_number');
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }
}
