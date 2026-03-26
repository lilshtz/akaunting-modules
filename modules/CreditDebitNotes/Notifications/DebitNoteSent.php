<?php

namespace Modules\CreditDebitNotes\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\CreditDebitNotes\Models\DebitNote;
use Modules\CreditDebitNotes\Models\NotePortalToken;

class DebitNoteSent extends Notification
{
    use Queueable;

    protected DebitNote $debitNote;
    protected NotePortalToken $portalToken;

    public function __construct(DebitNote $debitNote, NotePortalToken $portalToken)
    {
        $this->debitNote = $debitNote;
        $this->portalToken = $portalToken;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $portalUrl = route('credit-debit-notes.portal.show', $this->portalToken->token);
        $company = $this->debitNote->company;
        $companyName = $company ? $company->name : config('app.name');

        return (new MailMessage)
            ->subject(trans('credit-debit-notes::general.notifications.dn_sent_subject', [
                'number' => $this->debitNote->document_number,
                'company' => $companyName,
            ]))
            ->greeting(trans('credit-debit-notes::general.notifications.greeting', [
                'name' => $this->debitNote->contact_name,
            ]))
            ->line(trans('credit-debit-notes::general.notifications.dn_sent_body', [
                'company' => $companyName,
                'number' => $this->debitNote->document_number,
                'amount' => money($this->debitNote->amount, $this->debitNote->currency_code),
            ]))
            ->action(trans('credit-debit-notes::general.notifications.view_debit_note'), $portalUrl)
            ->line(trans('credit-debit-notes::general.notifications.footer'));
    }
}
