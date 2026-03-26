<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class Stock extends Model
{
    protected $table = 'inventory_stock';

    protected $fillable = [
        'item_id',
        'variant_id',
        'warehouse_id',
        'quantity',
        'reorder_level',
    ];

    protected $casts = [
        'quantity' => 'double',
        'reorder_level' => 'double',
    ];

    protected $appends = ['is_low_stock'];

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault(['name' => trans('general.na')]);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_level')
            ->whereColumn('quantity', '<', 'reorder_level');
    }

    public function getIsLowStockAttribute(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return (float) $this->quantity < (float) $this->reorder_level;
    }
}
