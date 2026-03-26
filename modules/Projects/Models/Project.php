<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_CANCELLED = 'cancelled';

    public const BILLING_PROJECT_HOURS = 'project_hours';
    public const BILLING_TASK_HOURS = 'task_hours';
    public const BILLING_FIXED_RATE = 'fixed_rate';

    protected $table = 'projects';

    protected $fillable = [
        'company_id',
        'contact_id',
        'name',
        'description',
        'status',
        'billing_type',
        'billing_rate',
        'budget',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'billing_rate' => 'double',
        'budget' => 'double',
        'start_date' => 'date',
        'end_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'progress_percentage',
        'status_label',
        'billing_type_label',
        'budget_display',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function contact()
    {
        return $this->belongsTo('App\Models\Common\Contact');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')->orderBy('position');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id')->orderBy('position');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function discussions()
    {
        return $this->hasMany(ProjectDiscussion::class, 'project_id')->whereNull('parent_id')->latest();
    }

    public function discussionEntries()
    {
        return $this->hasMany(ProjectDiscussion::class, 'project_id');
    }

    public function transactions()
    {
        return $this->hasMany(ProjectTransaction::class, 'project_id')->latest();
    }

    public function activities()
    {
        return $this->hasMany(ProjectActivity::class, 'project_id')->latest('created_at');
    }

    public function scopeStatus($query, ?string $status)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->relationLoaded('tasks') || $this->tasks()->exists()) {
            $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();
            $total = $tasks->count();

            if ($total > 0) {
                $done = $tasks->where('status', ProjectTask::STATUS_DONE)->count();

                return (int) round(($done / $total) * 100);
            }
        }

        $milestones = $this->relationLoaded('milestones') ? $this->milestones : $this->milestones()->get();
        $total = $milestones->count();

        if ($total === 0) {
            return 0;
        }

        $done = $milestones->whereNotNull('completed_at')->count();

        return (int) round(($done / $total) * 100);
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('projects::general.statuses.' . $this->status);
    }

    public function getBillingTypeLabelAttribute(): string
    {
        return trans('projects::general.billing_types.' . $this->billing_type);
    }

    public function getBudgetDisplayAttribute(): string
    {
        if ($this->budget === null) {
            return '-';
        }

        return money($this->budget, setting('default.currency', 'USD'));
    }
}
