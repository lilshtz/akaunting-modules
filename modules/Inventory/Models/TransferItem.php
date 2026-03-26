<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class TransferItem extends Model
{
    protected $table = 'inventory_transfer_items';

    protected $fillable = [
        'transfer_order_id',
        'item_id',
        'variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'double',
    ];

    public function transferOrder()
    {
        return $this->belongsTo(TransferOrder::class, 'transfer_order_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault(['name' => trans('general.na')]);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}
