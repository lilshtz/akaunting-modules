<?php

namespace Modules\Crm\Models;

use App\Abstracts\Model;

class CrmCompany extends Model
{
    protected $table = 'crm_companies';

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'currency',
        'default_stage',
    ];

    public function contacts()
    {
        return $this->hasMany(CrmContact::class, 'crm_company_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
