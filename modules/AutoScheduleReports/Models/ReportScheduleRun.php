<?php

namespace Modules\AutoScheduleReports\Models;

use App\Abstracts\Model;

class ReportScheduleRun extends Model
{
    protected $table = 'report_schedule_runs';

    protected $fillable = [
        'schedule_id',
        'ran_at',
        'file_path',
        'status',
        'error_message',
        'emailed_at',
    ];

    protected $casts = [
        'ran_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(ReportSchedule::class, 'schedule_id');
    }
}
