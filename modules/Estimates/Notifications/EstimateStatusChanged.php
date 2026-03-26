<?php

namespace Modules\Estimates\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Estimates\Models\Estimate;

class EstimateStatusChanged extends Notification
{
    use Queueable;

    protected Estimate $estimate;
    protected string $action;
    protected string $reason;

    public function __construct(Estimate $estimate, string $action, string $reason = '')
    {
        $this->estimate = $estimate;
        $this->action = $action;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(trans('estimates::general.notifications.status_changed_subject', [
                'number' => $this->estimate->document_number,
                'status' => $this->action,
            ]))
            ->greeting(trans('estimates::general.notifications.status_changed_greeting'))
            ->line(trans('estimates::general.notifications.status_changed_body', [
                'customer' => $this->estimate->contact_name,
                'action' => $this->action,
                'number' => $this->estimate->document_number,
            ]));

        if ($this->reason) {
            $message->line(trans('estimates::general.notifications.reason', [
                'reason' => $this->reason,
            ]));
        }

        $message->action(
            trans('estimates::general.notifications.view_estimate_admin'),
            route('estimates.estimates.show', $this->estimate->id)
        );

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'document_number' => $this->estimate->document_number,
            'contact_name' => $this->estimate->contact_name,
            'action' => $this->action,
            'reason' => $this->reason,
            'message' => trans('estimates::general.notifications.status_changed_body', [
                'customer' => $this->estimate->contact_name,
                'action' => $this->action,
                'number' => $this->estimate->document_number,
            ]),
        ];
    }
}
