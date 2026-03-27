<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_ASSET,
        self::TYPE_LIABILITY,
        self::TYPE_EQUITY,
        self::TYPE_INCOME,
        self::TYPE_EXPENSE,
    ];

    protected $table = 'double_entry_accounts';

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'detail_type',
        'description',
        'opening_balance',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'double',
        'enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function defaults()
    {
        return $this->hasMany(AccountDefault::class, 'account_id');
    }

    public function getNormalBalanceAttribute(): string
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true) ? JournalLine::DEBIT : JournalLine::CREDIT;
    }
}
