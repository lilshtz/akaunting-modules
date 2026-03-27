<?php

namespace Modules\Pos\Models;

use App\Abstracts\Model;

class PosSetting extends Model
{
    protected $table = 'pos_settings';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'company_id',
        'receipt_width',
        'default_payment_method',
        'auto_create_invoice',
        'next_order_number',
    ];

    protected $casts = [
        'receipt_width' => 'integer',
        'auto_create_invoice' => 'boolean',
        'next_order_number' => 'integer',
    ];
}
