<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;

class ProjectDiscussion extends Model
{
    protected $table = 'project_discussions';

    protected $fillable = [
        'project_id',
        'user_id',
        'parent_id',
        'body',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User', 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->with('user')->oldest();
    }
}
