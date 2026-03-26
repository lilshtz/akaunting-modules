<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class Adjustment extends Model
{
    protected $table = 'inventory_adjustments';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'item_id',
        'variant_id',
        'quantity',
        'reason',
        'description',
        'date',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'double',
        'date' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault(['name' => trans('general.na')]);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User', 'user_id');
    }
}
