<?php

namespace Modules\Estimates\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Estimates\Models\Estimate;
use Modules\Estimates\Models\EstimatePortalToken;

class EstimateSent extends Notification
{
    use Queueable;

    protected Estimate $estimate;
    protected EstimatePortalToken $portalToken;

    public function __construct(Estimate $estimate, EstimatePortalToken $portalToken)
    {
        $this->estimate = $estimate;
        $this->portalToken = $portalToken;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $portalUrl = route('estimates.portal.show', $this->portalToken->token);
        $company = $this->estimate->company;
        $companyName = $company ? $company->name : config('app.name');

        return (new MailMessage)
            ->subject(trans('estimates::general.notifications.estimate_sent_subject', [
                'number' => $this->estimate->document_number,
                'company' => $companyName,
            ]))
            ->greeting(trans('estimates::general.notifications.greeting', [
                'name' => $this->estimate->contact_name,
            ]))
            ->line(trans('estimates::general.notifications.estimate_sent_body', [
                'company' => $companyName,
                'number' => $this->estimate->document_number,
                'amount' => money($this->estimate->amount, $this->estimate->currency_code),
            ]))
            ->when($this->estimate->due_at, function ($message) {
                $message->line(trans('estimates::general.notifications.expires_on', [
                    'date' => $this->estimate->due_at->format('M d, Y'),
                ]));
            })
            ->action(trans('estimates::general.notifications.view_estimate'), $portalUrl)
            ->line(trans('estimates::general.notifications.estimate_sent_footer'));
    }
}
