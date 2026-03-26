<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class TransferOrder extends Model
{
    protected $table = 'inventory_transfer_orders';

    protected $fillable = [
        'company_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class, 'transfer_order_id');
    }
}
