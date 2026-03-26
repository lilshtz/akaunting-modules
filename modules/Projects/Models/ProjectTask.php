<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;
use Modules\Employees\Models\Employee;

class ProjectTask extends Model
{
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';

    protected $table = 'project_tasks';

    protected $fillable = [
        'milestone_id',
        'project_id',
        'name',
        'description',
        'assignee_id',
        'priority',
        'status',
        'estimated_hours',
        'position',
    ];

    protected $casts = [
        'estimated_hours' => 'double',
    ];

    protected $appends = [
        'status_label',
        'priority_label',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestone()
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    public function timesheets()
    {
        return $this->hasMany(ProjectTimesheet::class, 'task_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('projects::general.task_statuses.' . $this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return trans('projects::general.priorities.' . $this->priority);
    }
}
