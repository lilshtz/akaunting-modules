<?php

namespace Modules\Appointments\Models;

use App\Abstracts\Model;
use App\Models\Auth\User;
use Modules\Employees\Models\Employee;

class LeaveRequest extends Model
{
    public const TYPE_VACATION = 'vacation';
    public const TYPE_SICK = 'sick';
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REFUSED = 'refused';

    public const TYPES = [
        self::TYPE_VACATION,
        self::TYPE_SICK,
        self::TYPE_PERSONAL,
        self::TYPE_OTHER,
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REFUSED,
    ];

    protected $table = 'leave_requests';

    protected $fillable = [
        'company_id',
        'employee_id',
        'approver_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'status',
        'reason',
        'approved_at',
        'refused_at',
        'refusal_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'double',
        'approved_at' => 'datetime',
        'refused_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('appointments::general.leave_statuses.' . $this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return trans('appointments::general.leave_types.' . $this->type);
    }
}
