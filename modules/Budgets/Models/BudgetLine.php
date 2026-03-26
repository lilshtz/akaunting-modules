<?php

namespace Modules\Budgets\Models;

use App\Abstracts\Model;

class BudgetLine extends Model
{
    protected $table = 'budget_lines';

    protected $fillable = [
        'budget_id',
        'account_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function account()
    {
        return $this->belongsTo('Modules\DoubleEntry\Models\Account', 'account_id');
    }
}
