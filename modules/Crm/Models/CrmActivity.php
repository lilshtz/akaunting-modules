<?php

namespace Modules\Crm\Models;

use App\Abstracts\Model;

class CrmActivity extends Model
{
    public const TYPE_CALL = 'call';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_EMAIL = 'email';
    public const TYPE_NOTE = 'note';
    public const TYPE_TASK = 'task';

    public $timestamps = false;

    protected $table = 'crm_activities';

    protected $fillable = [
        'company_id',
        'crm_contact_id',
        'crm_deal_id',
        'type',
        'description',
        'scheduled_at',
        'completed_at',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User', 'user_id');
    }
}
