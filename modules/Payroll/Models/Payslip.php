<?php

namespace Modules\Payroll\Models;

use App\Abstracts\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Employees\Models\Employee;

class Payslip extends Model
{
    protected $table = 'payslips';

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'gross',
        'total_benefits',
        'total_deductions',
        'net',
        'pdf_path',
        'emailed_at',
    ];

    protected $casts = [
        'gross' => 'double',
        'total_benefits' => 'double',
        'total_deductions' => 'double',
        'net' => 'double',
        'emailed_at' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(PayslipItem::class, 'payslip_id');
    }

    public function benefitItems()
    {
        return $this->items()->where('type', 'benefit');
    }

    public function deductionItems()
    {
        return $this->items()->where('type', 'deduction');
    }

    public function hasStoredPdf(): bool
    {
        return ! empty($this->pdf_path) && Storage::disk('public')->exists($this->pdf_path);
    }

    public function getFileNameAttribute(): string
    {
        return 'payslip-' . $this->employee_id . '-' . $this->run?->period_end?->format('Y-m-d') . '.pdf';
    }
}
