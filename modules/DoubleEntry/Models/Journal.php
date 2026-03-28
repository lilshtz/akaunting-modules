<?php

namespace Modules\DoubleEntry\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
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
        'next_recurring_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $sortable = ['date', 'reference', 'status', 'basis', 'created_at'];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function balanced(): bool
    {
        $debit = (float) $this->lines->sum('debit');
        $credit = (float) $this->lines->sum('credit');

        return round($debit, 4) === round($credit, 4);
    }
}
