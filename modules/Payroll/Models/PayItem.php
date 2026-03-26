<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;

class PayItem extends Model
{
    protected $table = 'pay_items';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'default_amount',
        'is_percentage',
        'enabled',
    ];

    protected $casts = [
        'default_amount' => 'double',
        'is_percentage' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
