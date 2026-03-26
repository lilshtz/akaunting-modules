<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;

class ProjectActivity extends Model
{
    public $timestamps = false;

    protected $table = 'project_activities';

    protected $fillable = [
        'project_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User', 'user_id');
    }

    public static function log(Project $project, string $action, ?string $entityType, ?int $entityId, string $description): self
    {
        return static::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
