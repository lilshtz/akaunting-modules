<?php

namespace Modules\Projects\Models;

use App\Abstracts\Model;
use App\Models\Auth\User;
use Carbon\Carbon;

class ProjectTimesheet extends Model
{
    protected $table = 'project_timesheets';

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'ended_at',
        'hours',
        'billable',
        'description',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'hours' => 'double',
        'billable' => 'boolean',
    ];

    protected $appends = [
        'tracked_hours',
        'entry_date',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeRunning($query)
    {
        return $query->whereNull('ended_at');
    }

    public function getTrackedHoursAttribute(): float
    {
        if ($this->hours !== null) {
            return (float) $this->hours;
        }

        if (empty($this->started_at)) {
            return 0.0;
        }

        $end = $this->ended_at ?: now();
        $seconds = max(0, Carbon::parse($this->started_at)->diffInSeconds($end));

        if ($seconds === 0) {
            return 0.0;
        }

        return max(0.01, round($seconds / 3600, 2));
    }

    public function getEntryDateAttribute(): ?string
    {
        return $this->started_at?->toDateString();
    }
}
