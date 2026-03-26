<?php

namespace Modules\Employees\Models;

use App\Abstracts\Model;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'name',
        'file_path',
        'type',
        'uploaded_at',
        'notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public static function documentTypes(): array
    {
        return [
            'w9' => 'W-9',
            'insurance' => 'Insurance',
            'license' => 'License',
            'agreement' => 'Agreement',
            'other' => 'Other',
        ];
    }
}
