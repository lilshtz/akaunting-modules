<?php

namespace Modules\Pos\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrderItem extends Model
{
    protected $table = 'pos_order_items';

    protected $fillable = [
        'order_id',
        'item_id',
        'name',
        'sku',
        'quantity',
        'price',
        'discount',
        'tax',
        'total',
    ];

    protected $casts = [
        'quantity' => 'double',
        'price' => 'double',
        'discount' => 'double',
        'tax' => 'double',
        'total' => 'double',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault([
            'name' => trans('general.na'),
        ]);
    }
}
