<?php

namespace Modules\Inventory\Models;

use App\Abstracts\Model;

class Warehouse extends Model
{
    protected $table = 'inventory_warehouses';

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'email',
        'phone',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $sortable = ['name', 'email', 'phone', 'enabled', 'created_at'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'warehouse_id');
    }

    public function histories()
    {
        return $this->hasMany(History::class, 'warehouse_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function getLineActionsAttribute(): array
    {
        return [
            [
                'title' => trans('general.edit'),
                'icon' => 'edit',
                'url' => route('inventory.warehouses.edit', $this->id),
            ],
            [
                'type' => 'delete',
                'icon' => 'delete',
                'route' => 'inventory.warehouses.destroy',
                'model' => $this,
            ],
        ];
    }
}
