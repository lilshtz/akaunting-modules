<?php

namespace Modules\ExpenseClaims\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ExpenseClaims\Models\ExpenseClaim;

class ClaimSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected ExpenseClaim $claim)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('expense-claims::general.notifications.submitted_subject', ['claim' => $this->claim->claim_number ?: '#' . $this->claim->id]))
            ->line(trans('expense-claims::general.notifications.submitted_body', ['employee' => $this->claim->employee_name]))
            ->action(trans('expense-claims::general.view_claim'), route('expense-claims.claims.show', $this->claim->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'status' => $this->claim->status,
            'message' => trans('expense-claims::general.notifications.submitted_body', ['employee' => $this->claim->employee_name]),
        ];
    }
}
