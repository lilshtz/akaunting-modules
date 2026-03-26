<?php

namespace Modules\SalesPurchaseOrders\Models;

use App\Abstracts\Model;

class OrderHistory extends Model
{
    protected $table = 'order_histories';

    protected $fillable = [
        'company_id',
        'document_id',
        'status',
        'notify',
        'description',
    ];

    public function document()
    {
        return $this->belongsTo('App\Models\Document\Document');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
