<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $table = 'double_entry_accounts';

    protected $with = ['children'];

    protected $fillable = [
        'company_id',
        'parent_id',
        'type',
        'code',
        'name',
        'description',
        'opening_balance',
        'enabled',
    ];

    protected $casts = [
        'opening_balance' => 'double',
        'enabled' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('code');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function defaults()
    {
        return $this->hasMany(AccountDefault::class, 'account_id');
    }

    /**
     * Scope to only enabled accounts.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope by account type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the current balance for this account.
     */
    public function getBalanceAttribute()
    {
        $debits = $this->journalLines()
            ->where('company_id', $this->company_id)
            ->whereHas('journal', function ($q) {
                $q->where('status', 'posted');
            })
            ->sum('debit');

        $credits = $this->journalLines()
            ->where('company_id', $this->company_id)
            ->whereHas('journal', function ($q) {
                $q->where('status', 'posted');
            })
            ->sum('credit');

        // Asset & Expense: normal debit balance; Liability, Equity, Income: normal credit balance
        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits + ($this->opening_balance ?? 0);
        }

        return $credits - $debits + ($this->opening_balance ?? 0);
    }
}
