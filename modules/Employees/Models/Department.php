<?php

namespace Modules\Employees\Models;

use App\Abstracts\Model;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'manager_id',
    ];

    protected $sortable = ['name'];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function activeEmployees()
    {
        return $this->hasMany(Employee::class, 'department_id')->where('status', 'active');
    }

    public function scopeName($query, $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }

    public function getLineActionsAttribute(): array
    {
        $actions = [];

        $actions[] = [
            'title' => trans('general.edit'),
            'icon' => 'edit',
            'url' => route('employees.departments.edit', $this->id),
            'attributes' => [
                'id' => 'index-line-actions-edit-department-' . $this->id,
            ],
        ];

        $actions[] = [
            'type' => 'delete',
            'icon' => 'delete',
            'route' => 'employees.departments.destroy',
            'model' => $this,
            'attributes' => [
                'id' => 'index-line-actions-delete-department-' . $this->id,
            ],
        ];

        return $actions;
    }
}
