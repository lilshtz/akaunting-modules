<?php

namespace Modules\SalesPurchaseOrders\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderSent extends Notification
{
    use Queueable;

    protected Model $document;
    protected string $orderType;

    public function __construct(Model $document, string $orderType = '')
    {
        $this->document = $document;
        $this->orderType = $orderType ?: ($document->type === 'sales-order' ? 'sales-order' : 'purchase-order');
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $company = $this->document->company;
        $companyName = $company ? $company->name : config('app.name');
        $typeLabel = $this->orderType === 'sales-order'
            ? trans('sales-purchase-orders::general.sales_order')
            : trans('sales-purchase-orders::general.purchase_order');

        return (new MailMessage)
            ->subject(trans('sales-purchase-orders::general.notifications.order_sent_subject', [
                'type' => $typeLabel,
                'number' => $this->document->document_number,
                'company' => $companyName,
            ]))
            ->greeting(trans('sales-purchase-orders::general.notifications.greeting', [
                'name' => $this->document->contact_name,
            ]))
            ->line(trans('sales-purchase-orders::general.notifications.order_sent_body', [
                'company' => $companyName,
                'type' => $typeLabel,
                'number' => $this->document->document_number,
                'amount' => money($this->document->amount, $this->document->currency_code),
            ]))
            ->when($this->document->due_at, function ($message) {
                $message->line(trans('sales-purchase-orders::general.notifications.delivery_date', [
                    'date' => $this->document->due_at->format('M d, Y'),
                ]));
            })
            ->action(trans('sales-purchase-orders::general.notifications.view_order'), url('/'))
            ->line(trans('sales-purchase-orders::general.notifications.order_sent_footer'));
    }
}
