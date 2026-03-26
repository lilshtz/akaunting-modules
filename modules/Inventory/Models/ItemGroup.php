<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class ItemGroup extends Model
{
    protected $table = 'inventory_item_groups';

    protected $fillable = [
        'company_id',
        'name',
        'description',
    ];

    public function items()
    {
        return $this->belongsToMany('App\Models\Common\Item', 'inventory_item_group_items', 'item_group_id', 'item_id');
    }
}
