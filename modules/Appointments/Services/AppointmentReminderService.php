<?php

namespace Modules\Appointments\Services;

use Modules\Appointments\Models\Appointment;
use Modules\Appointments\Notifications\AppointmentReminder;

class AppointmentReminderService
{
    public function sendDueReminders(int $companyId): int
    {
        $appointments = Appointment::where('company_id', $companyId)
            ->scheduled()
            ->whereDate('date', now()->addDay()->toDateString())
            ->where('reminder_sent', false)
            ->with('contact')
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            if (! $appointment->contact || empty($appointment->contact->email)) {
                continue;
            }

            $appointment->contact->notify(new AppointmentReminder($appointment));
            $appointment->update(['reminder_sent' => true]);
            $sent++;
        }

        return $sent;
    }
}
