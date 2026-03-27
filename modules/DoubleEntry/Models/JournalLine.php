<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class JournalLine extends Model
{
    public const DEBIT = 'debit';
    public const CREDIT = 'credit';

    protected $table = 'double_entry_journal_lines';

    protected $fillable = [
        'company_id',
        'journal_id',
        'account_id',
        'line_number',
        'entry_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
