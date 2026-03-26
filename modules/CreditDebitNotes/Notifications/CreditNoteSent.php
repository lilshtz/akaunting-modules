<?php

namespace Modules\CreditDebitNotes\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\CreditDebitNotes\Models\CreditNote;
use Modules\CreditDebitNotes\Models\NotePortalToken;

class CreditNoteSent extends Notification
{
    use Queueable;

    protected CreditNote $creditNote;
    protected NotePortalToken $portalToken;

    public function __construct(CreditNote $creditNote, NotePortalToken $portalToken)
    {
        $this->creditNote = $creditNote;
        $this->portalToken = $portalToken;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $portalUrl = route('credit-debit-notes.portal.show', $this->portalToken->token);
        $company = $this->creditNote->company;
        $companyName = $company ? $company->name : config('app.name');

        return (new MailMessage)
            ->subject(trans('credit-debit-notes::general.notifications.cn_sent_subject', [
                'number' => $this->creditNote->document_number,
                'company' => $companyName,
            ]))
            ->greeting(trans('credit-debit-notes::general.notifications.greeting', [
                'name' => $this->creditNote->contact_name,
            ]))
            ->line(trans('credit-debit-notes::general.notifications.cn_sent_body', [
                'company' => $companyName,
                'number' => $this->creditNote->document_number,
                'amount' => money($this->creditNote->amount, $this->creditNote->currency_code),
            ]))
            ->action(trans('credit-debit-notes::general.notifications.view_credit_note'), $portalUrl)
            ->line(trans('credit-debit-notes::general.notifications.footer'));
    }
}
