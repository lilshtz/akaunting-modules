<?php

namespace Modules\Employees\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payroll\Models\Payslip;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'contact_id',
        'department_id',
        'user_id',
        'photo_path',
        'hire_date',
        'birthday',
        'salary',
        'salary_type',
        'bank_name',
        'bank_account',
        'bank_routing',
        'type',
        'classification',
        'status',
        'terminated_at',
        'notes',
    ];

    protected $casts = [
        'salary' => 'double',
        'hire_date' => 'date',
        'birthday' => 'date',
        'terminated_at' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'bank_account',
        'bank_routing',
    ];

    protected $sortable = ['hire_date', 'status', 'type', 'classification'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function contact()
    {
        return $this->belongsTo('App\Models\Common\Contact');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Auth\User');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeClassification($query, string $classification)
    {
        return $query->where('classification', $classification);
    }

    public function scopeDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function getNameAttribute(): string
    {
        return $this->contact?->name ?? 'Unknown';
    }

    public function getEmailAttribute(): ?string
    {
        return $this->contact?->email;
    }

    public function getSalaryDisplayAttribute(): string
    {
        if (! $this->salary) {
            return '-';
        }

        $amount = number_format($this->salary, 2);
        $type = $this->salary_type ? trans('employees::general.salary_types.' . $this->salary_type) : '';

        return "$amount / $type";
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('employees::general.statuses.' . $this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return trans('employees::general.types.' . $this->type);
    }

    public function getClassificationLabelAttribute(): string
    {
        return trans('employees::general.classifications.' . $this->classification);
    }

    public function terminate(?string $date = null): void
    {
        $this->update([
            'status' => 'terminated',
            'terminated_at' => $date ?? now(),
        ]);
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('general.show'),
            'icon' => 'visibility',
            'url' => route('employees.employees.show', $this->id),
            'attributes' => [
                'id' => 'index-line-actions-show-employee-' . $this->id,
            ],
        ];

        $actions[] = [
            'title' => trans('general.edit'),
            'icon' => 'edit',
            'url' => route('employees.employees.edit', $this->id),
            'attributes' => [
                'id' => 'index-line-actions-edit-employee-' . $this->id,
            ],
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'employees.employees.destroy',
            'model' => $this,
            'attributes' => [
                'id' => 'index-line-actions-delete-employee-' . $this->id,
            ],
        ];

        return $actions;
    }
}
