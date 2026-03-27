<?php

namespace Modules\Roles\Models;

class Role extends \App\Models\Auth\Role
{
    public function modulePermissions()
    {
        return $this->hasMany(RoleModulePermission::class, 'role_id');
    }

    public function userCompanyRoles()
    {
        return $this->hasMany(UserCompanyRole::class, 'role_id');
    }
}
