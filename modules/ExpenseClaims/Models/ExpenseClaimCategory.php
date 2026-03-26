<?php

namespace Modules\ExpenseClaims\Models;

use App\Abstracts\Model;

class ExpenseClaimCategory extends Model
{
    protected $table = 'expense_claim_categories';

    protected $fillable = [
        'company_id',
        'name',
        'color',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'bool',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function items()
    {
        return $this->hasMany(ExpenseClaimItem::class, 'category_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
