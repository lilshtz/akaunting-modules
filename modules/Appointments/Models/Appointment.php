<?php

namespace Modules\Appointments\Models;

use App\Abstracts\Model;
use App\Models\Auth\User;
use App\Models\Common\Contact;
use Carbon\Carbon;

class Appointment extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
    ];

    protected $table = 'appointments';

    protected $fillable = [
        'company_id',
        'contact_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'location',
        'status',
        'notes',
        'reminder_sent',
    ];

    protected $casts = [
        'date' => 'date',
        'reminder_sent' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function getStatusLabelAttribute(): string
    {
        return trans('appointments::general.statuses.' . $this->status);
    }

    public function getStartsAtAttribute(): Carbon
    {
        return Carbon::parse($this->date->toDateString() . ' ' . $this->start_time);
    }

    public function getEndsAtAttribute(): Carbon
    {
        return Carbon::parse($this->date->toDateString() . ' ' . $this->end_time);
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->contact?->name ?: trans('general.na');
    }

    public function getLineActionsAttribute(): array
    {
        return [
            [
                'title' => trans('general.show'),
                'icon' => 'visibility',
                'url' => route('appointments.show', $this->id),
            ],
            [
                'title' => trans('general.edit'),
                'icon' => 'edit',
                'url' => route('appointments.edit', $this->id),
            ],
            [
                'type' => 'delete',
                'icon' => 'delete',
                'route' => 'appointments.destroy',
                'model' => $this,
            ],
        ];
    }
}
