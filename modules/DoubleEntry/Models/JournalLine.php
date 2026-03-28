<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $table = 'double_entry_journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    protected $tenantable = false;

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id')->withTrashed();
    }
}
