<?php

namespace Modules\Appointments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Appointments\Models\LeaveRequest;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected LeaveRequest $request)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $leave = $this->request->loadMissing('employee.contact');

        return (new MailMessage)
            ->subject(trans('appointments::general.notifications.leave_submitted_subject', [
                'employee' => $leave->employee?->name,
            ]))
            ->line(trans('appointments::general.notifications.leave_submitted_body', [
                'employee' => $leave->employee?->name,
                'dates' => $leave->start_date?->format('M d, Y') . ' - ' . $leave->end_date?->format('M d, Y'),
            ]))
            ->action(trans('appointments::general.view_leave_request'), route('appointments.leave.show', $leave->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_request_id' => $this->request->id,
            'status' => $this->request->status,
            'message' => trans('appointments::general.notifications.leave_submitted_body', [
                'employee' => $this->request->employee?->name,
                'dates' => $this->request->start_date?->format('M d, Y') . ' - ' . $this->request->end_date?->format('M d, Y'),
            ]),
        ];
    }
}
