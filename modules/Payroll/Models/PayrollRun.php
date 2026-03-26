<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;

class PayrollRun extends Model
{
    protected $table = 'payroll_runs';

    protected $fillable = [
        'company_id',
        'pay_calendar_id',
        'period_start',
        'period_end',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'approved_by',
        'processed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_gross' => 'double',
        'total_deductions' => 'double',
        'total_net' => 'double',
        'processed_at' => 'datetime',
    ];

    public function calendar()
    {
        return $this->belongsTo(PayCalendar::class, 'pay_calendar_id');
    }

    public function employees()
    {
        return $this->hasMany(PayrollRunEmployee::class, 'payroll_run_id');
    }

    public function approver()
    {
        return $this->belongsTo('App\Models\Auth\User', 'approved_by');
    }
}
