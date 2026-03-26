<?php

namespace Modules\CreditDebitNotes\Models;

use App\Abstracts\Model;

class CreditNoteApplication extends Model
{
    protected $table = 'credit_note_applications';

    protected $fillable = [
        'company_id',
        'credit_note_id',
        'invoice_id',
        'amount',
        'date',
    ];

    protected $casts = [
        'amount' => 'double',
        'date' => 'date',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Models\Document\Document', 'invoice_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }
}
