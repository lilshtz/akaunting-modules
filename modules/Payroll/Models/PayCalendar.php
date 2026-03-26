<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;
use Modules\Employees\Models\Employee;

class PayCalendar extends Model
{
    protected $table = 'pay_calendars';

    protected $fillable = [
        'company_id',
        'name',
        'frequency',
        'start_date',
        'next_run_date',
        'enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_run_date' => 'date',
        'enabled' => 'boolean',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'pay_calendar_employees', 'pay_calendar_id', 'employee_id');
    }

    public function runs()
    {
        return $this->hasMany(PayrollRun::class, 'pay_calendar_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
