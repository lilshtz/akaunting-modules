<?php

namespace Modules\Crm\Models;

use App\Abstracts\Model;

class CrmDeal extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_DELETED = 'deleted';

    protected $table = 'crm_deals';

    protected $fillable = [
        'company_id',
        'crm_contact_id',
        'name',
        'value',
        'stage_id',
        'expected_close',
        'status',
        'invoice_id',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close' => 'date',
        'closed_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function stage()
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Models\Document\Document', 'invoice_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'crm_deal_id')->latest('scheduled_at')->latest('created_at');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeVisible($query)
    {
        return $query->where('status', '<>', static::STATUS_DELETED);
    }
}
