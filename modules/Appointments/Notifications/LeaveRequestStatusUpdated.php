<?php

namespace Modules\Appointments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Appointments\Models\LeaveRequest;

class LeaveRequestStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(protected LeaveRequest $request, protected string $reason = '')
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $leave = $this->request->loadMissing('employee.contact');
        $mail = (new MailMessage)
            ->subject(trans('appointments::general.notifications.leave_status_subject', [
                'status' => trans('appointments::general.leave_statuses.' . $leave->status),
            ]))
            ->line(trans('appointments::general.notifications.leave_status_body', [
                'status' => trans('appointments::general.leave_statuses.' . $leave->status),
                'dates' => $leave->start_date?->format('M d, Y') . ' - ' . $leave->end_date?->format('M d, Y'),
            ]))
            ->action(trans('appointments::general.view_leave_request'), route('appointments.leave.show', $leave->id));

        if ($this->reason !== '') {
            $mail->line(trans('appointments::general.refusal_reason') . ': ' . $this->reason);
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_request_id' => $this->request->id,
            'status' => $this->request->status,
            'reason' => $this->reason,
        ];
    }
}
