<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;

class PayslipItem extends Model
{
    protected $table = 'payslip_items';

    protected $fillable = [
        'payslip_id',
        'pay_item_id',
        'type',
        'name',
        'amount',
        'is_percentage',
        'percentage_of',
    ];

    protected $casts = [
        'amount' => 'double',
        'is_percentage' => 'boolean',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function payItem()
    {
        return $this->belongsTo(PayItem::class, 'pay_item_id');
    }
}
