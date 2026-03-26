<?php

namespace Modules\Stripe\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripePayment extends Model
{
    use HasFactory;

    protected $table = 'stripe_payments';

    protected $fillable = [
        'company_id',
        'document_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_session_id',
        'amount',
        'currency',
        'status',
        'refund_id',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    /**
     * Get the company that owns the payment.
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    /**
     * Get the document (invoice) associated with the payment.
     */
    public function document()
    {
        return $this->belongsTo('App\Models\Document\Document');
    }

    /**
     * Scope to only include payments with a given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
