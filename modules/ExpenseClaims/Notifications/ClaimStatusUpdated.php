<?php

namespace Modules\ExpenseClaims\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ExpenseClaims\Models\ExpenseClaim;

class ClaimStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(protected ExpenseClaim $claim, protected string $action, protected string $reason = '')
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(trans('expense-claims::general.notifications.status_subject', [
                'claim' => $this->claim->claim_number ?: '#' . $this->claim->id,
                'status' => trans('expense-claims::general.statuses.' . $this->action),
            ]))
            ->line(trans('expense-claims::general.notifications.status_body', [
                'claim' => $this->claim->claim_number ?: '#' . $this->claim->id,
                'status' => trans('expense-claims::general.statuses.' . $this->action),
            ]))
            ->action(trans('expense-claims::general.view_claim'), route('expense-claims.claims.show', $this->claim->id));

        if ($this->reason !== '') {
            $mail->line(trans('expense-claims::general.refusal_reason') . ': ' . $this->reason);
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'status' => $this->action,
            'reason' => $this->reason,
            'message' => trans('expense-claims::general.notifications.status_body', [
                'claim' => $this->claim->claim_number ?: '#' . $this->claim->id,
                'status' => trans('expense-claims::general.statuses.' . $this->action),
            ]),
        ];
    }
}
