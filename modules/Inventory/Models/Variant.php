<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class Variant extends Model
{
    protected $table = 'inventory_variants';

    protected $fillable = [
        'item_id',
        'name',
        'sku',
        'attributes_json',
        'cost_price',
        'sale_price',
    ];

    protected $casts = [
        'attributes_json' => 'array',
        'cost_price' => 'double',
        'sale_price' => 'double',
    ];

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault(['name' => trans('general.na')]);
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'variant_id');
    }

    public function histories()
    {
        return $this->hasMany(History::class, 'variant_id');
    }
}
