<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status' => 'string',
        'basis' => 'string',
        'next_recurring_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['date', 'reference', 'status', 'basis', 'created_at'];

    protected static function booted(): void
    {
        static::creating(function (self $journal): void {
            if (blank($journal->reference)) {
                $journal->reference = static::generateNextReference((int) $journal->company_id);
            }
        });
    }

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalAttribute(): float
    {
        if (array_key_exists('total_amount', $this->attributes)) {
            return round((float) $this->attributes['total_amount'], 4);
        }

        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get(['debit']);

        return round((float) $lines->sum('debit'), 4);
    }

    public function isBalanced(): bool
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get(['debit', 'credit']);
        $debit = round((float) $lines->sum('debit'), 4);
        $credit = round((float) $lines->sum('credit'), 4);

        return $debit === $credit;
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public static function generateNextReference(?int $companyId = null): string
    {
        $companyId ??= company_id();

        $maxReference = static::query()
            ->byCompany($companyId)
            ->withTrashed()
            ->where('reference', 'like', 'JE-%')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(reference, 4) AS UNSIGNED)) as max_reference')
            ->value('max_reference');

        return 'JE-' . str_pad((string) (((int) $maxReference) + 1), 4, '0', STR_PAD_LEFT);
    }
}
