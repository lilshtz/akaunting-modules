<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;

class JournalLine extends Model
{
    protected $table = 'double_entry_journal_lines';

    public $timestamps = false;

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'double',
        'credit' => 'double',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
