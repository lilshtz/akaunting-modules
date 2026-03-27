<?php

namespace Modules\Pos\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosOrder extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'pos_orders';

    protected $fillable = [
        'company_id',
        'contact_id',
        'order_number',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'tab_name',
    ];

    protected $casts = [
        'subtotal' => 'double',
        'tax' => 'double',
        'discount' => 'double',
        'total' => 'double',
        'paid_amount' => 'double',
        'change_amount' => 'double',
    ];

    protected $appends = [
        'status_label',
        'payment_method_label',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo('App\Models\Common\Contact')->withDefault([
            'name' => trans('general.na'),
        ]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'order_id');
    }

    public function scopeOwnedByCompany($query, ?int $companyId = null)
    {
        return $query->where('company_id', $companyId ?: company_id());
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('pos::general.statuses.' . $this->status);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return trans('pos::general.payment_methods.' . $this->payment_method, [], app()->getLocale());
    }
}
