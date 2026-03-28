<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Services\AccountBalanceService;

class Account extends Model
{
    use SoftDeletes;

    protected $table = 'double_entry_accounts';

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'opening_balance',
        'enabled',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['code', 'name', 'type', 'opening_balance', 'enabled', 'created_at'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function scopeByCompany(Builder $query, ?int $companyId = null): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $companyId ?? company_id());
    }

    public function getBalanceAttribute(): float
    {
        $totals = DB::table('double_entry_journal_lines')
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journal_lines.account_id', $this->id)
            ->where('double_entry_journals.company_id', $this->company_id ?? company_id())
            ->where('double_entry_journals.status', 'posted')
            ->whereNull('double_entry_journals.deleted_at')
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.credit), 0) as credit_total')
            ->first();

        $openingBalance = (float) $this->opening_balance;
        $debitTotal = (float) ($totals->debit_total ?? 0);
        $creditTotal = (float) ($totals->credit_total ?? 0);
        $movement = AccountBalanceService::normalBalanceSide($this->type) === 'debit'
            ? $debitTotal - $creditTotal
            : $creditTotal - $debitTotal;

        return round($openingBalance + $movement, 4);
    }
}
