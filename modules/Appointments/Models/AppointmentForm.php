<?php

namespace Modules\Appointments\Models;

use App\Abstracts\Model;

class AppointmentForm extends Model
{
    protected $table = 'appointment_forms';

    protected $fillable = [
        'company_id',
        'name',
        'fields_json',
        'public_link',
        'enabled',
    ];

    protected $casts = [
        'fields_json' => 'array',
        'enabled' => 'boolean',
    ];

    public function getBookingUrlAttribute(): string
    {
        return route('signed.appointments.booking.show', ['token' => $this->public_link]);
    }
}
