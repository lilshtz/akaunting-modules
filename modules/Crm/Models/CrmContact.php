<?php

namespace Modules\Crm\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContact extends Model
{
    use SoftDeletes;

    public const SOURCE_WEB = 'web';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_COLD = 'cold';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_OTHER = 'other';

    public const STAGE_LEAD = 'lead';
    public const STAGE_SUBSCRIBER = 'subscriber';
    public const STAGE_OPPORTUNITY = 'opportunity';
    public const STAGE_CUSTOMER = 'customer';

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id',
        'crm_company_id',
        'name',
        'email',
        'phone',
        'source',
        'stage',
        'owner_user_id',
        'akaunting_contact_id',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function crmCompany()
    {
        return $this->belongsTo(CrmCompany::class, 'crm_company_id');
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\Auth\User', 'owner_user_id');
    }

    public function akauntingContact()
    {
        return $this->belongsTo('App\Models\Common\Contact', 'akaunting_contact_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'crm_contact_id')->latest('scheduled_at')->latest('created_at');
    }

    public function scopeStage($query, ?string $stage)
    {
        if (empty($stage)) {
            return $query;
        }

        return $query->where('stage', $stage);
    }

    public function scopeSource($query, ?string $source)
    {
        if (empty($source)) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeCrmCompany($query, $crmCompanyId)
    {
        if (empty($crmCompanyId)) {
            return $query;
        }

        return $query->where('crm_company_id', $crmCompanyId);
    }
}
