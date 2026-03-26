<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;

class ProjectMember extends Model
{
    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User', 'user_id');
    }
}
