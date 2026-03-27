<?php

namespace Modules\Roles\Models;

use App\Abstracts\Model;

class RoleModulePermission extends Model
{
    protected $table = 'role_module_permissions';

    protected $fillable = [
        'company_id',
        'role_id',
        'module_alias',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
