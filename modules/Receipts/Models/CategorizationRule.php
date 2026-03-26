<?php

namespace Modules\Receipts\Models;

use App\Abstracts\Model;

class CategorizationRule extends Model
{
    protected $table = 'receipt_categorization_rules';

    protected $fillable = [
        'company_id',
        'vendor_pattern',
        'category_id',
        'account_id',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Setting\Category');
    }
}
