<?php

namespace Modules\Appointments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Appointments\Models\Appointment;

class AppointmentReminder extends Notification
{
    use Queueable;

    public function __construct(protected Appointment $appointment)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['contact', 'user']);

        return (new MailMessage)
            ->subject(trans('appointments::general.notifications.reminder_subject', [
                'date' => $appointment->date?->format('M d, Y'),
            ]))
            ->greeting(trans('appointments::general.notifications.greeting', [
                'name' => $appointment->contact?->name ?: $notifiable->name,
            ]))
            ->line(trans('appointments::general.notifications.reminder_body', [
                'date' => $appointment->date?->format('M d, Y'),
                'start_time' => $appointment->start_time,
                'location' => $appointment->location ?: trans('general.na'),
            ]));
    }
}
