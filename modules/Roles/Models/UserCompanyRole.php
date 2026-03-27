<?php

namespace Modules\Roles\Models;

use App\Abstracts\Model;

class UserCompanyRole extends Model
{
    protected $table = 'user_company_roles';

    protected $fillable = [
        'company_id',
        'user_id',
        'role_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user()
    {
        return $this->belongsTo(user_model_class(), 'user_id');
    }
}
