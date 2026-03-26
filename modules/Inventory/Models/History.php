<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class History extends Model
{
    protected $table = 'inventory_history';

    protected $fillable = [
        'company_id',
        'item_id',
        'warehouse_id',
        'quantity_change',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'date',
    ];

    protected $casts = [
        'quantity_change' => 'double',
        'date' => 'datetime',
    ];

    protected $sortable = ['date', 'type', 'quantity_change', 'created_at'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Common\Item')->withDefault(['name' => trans('general.na')]);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
