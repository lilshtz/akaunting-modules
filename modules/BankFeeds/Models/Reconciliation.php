<?php

namespace Modules\BankFeeds\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DoubleEntry\Models\Account;

class Reconciliation extends Model
{
    protected $table = 'bank_feed_reconciliations';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'period_start',
        'period_end',
        'opening_balance',
        'closing_balance',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'bank_account_id', 'bank_account_id')
            ->where('company_id', $this->company_id)
            ->where('status', 'matched')
            ->whereNotNull('matched_journal_id')
            ->whereBetween('date', [$this->period_start, $this->period_end]);
    }
}
