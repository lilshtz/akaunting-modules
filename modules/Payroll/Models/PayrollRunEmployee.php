<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;
use Modules\Employees\Models\Employee;

class PayrollRunEmployee extends Model
{
    protected $table = 'payroll_run_employees';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'gross_amount',
        'benefit_amount',
        'deduction_amount',
        'net_amount',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'double',
        'benefit_amount' => 'double',
        'deduction_amount' => 'double',
        'net_amount' => 'double',
    ];

    public function run()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
