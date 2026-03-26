<?php

namespace Modules\SalesPurchaseOrders\Models;

use App\Abstracts\Model;

class OrderSetting extends Model
{
    protected $table = 'order_settings';

    protected $fillable = [
        'company_id',
        'so_prefix',
        'so_next_number',
        'po_prefix',
        'po_next_number',
        'default_terms',
        'template',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public static function getForCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'so_prefix' => 'SO-',
                'so_next_number' => 1,
                'po_prefix' => 'PO-',
                'po_next_number' => 1,
                'template' => 'default',
            ]
        );
    }

    public function generateSalesOrderNumber(): string
    {
        $number = $this->so_prefix . str_pad($this->so_next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('so_next_number');

        return $number;
    }

    public function generatePurchaseOrderNumber(): string
    {
        $number = $this->po_prefix . str_pad($this->po_next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('po_next_number');

        return $number;
    }
}
