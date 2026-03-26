<?php

namespace Modules\ExpenseClaims\Models;

use App\Abstracts\Model;

class ExpenseClaimItem extends Model
{
    protected $table = 'expense_claim_items';

    protected $fillable = [
        'claim_id',
        'category_id',
        'date',
        'description',
        'amount',
        'receipt_path',
        'notes',
        'paid_by_employee',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'double',
        'paid_by_employee' => 'bool',
    ];

    public function claim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'claim_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseClaimCategory::class, 'category_id');
    }
}
