<?php

namespace Modules\Payroll\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Services\PayslipService;

class PayslipReady extends Notification
{
    use Queueable;

    public function __construct(protected Payslip $payslip)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $payslip = $this->payslip->loadMissing(['run.calendar', 'employee.contact']);
        $run = $payslip->run;
        $mail = (new MailMessage)
            ->subject(trans('payroll::general.notifications.payslip_subject', [
                'employee' => $payslip->employee?->name,
                'period_end' => $run?->period_end?->format('M d, Y'),
            ]))
            ->greeting(trans('payroll::general.notifications.greeting', [
                'name' => $payslip->employee?->name,
            ]))
            ->line(trans('payroll::general.notifications.payslip_body', [
                'period_start' => $run?->period_start?->format('M d, Y'),
                'period_end' => $run?->period_end?->format('M d, Y'),
                'net' => money($payslip->net, setting('default.currency', 'USD')),
            ]))
            ->line(trans('payroll::general.notifications.portal_hint'));

        if ($payslip->employee?->user_id) {
            $mail->action(trans('payroll::general.notifications.view_payslip'), url('portal/payroll/payslips/' . $payslip->id));
        }

        $pdf = app(PayslipService::class)->buildPdf($payslip);

        if ($pdf !== null) {
            $mail->attachData($pdf, $payslip->file_name, ['mime' => 'application/pdf']);
        }

        return $mail;
    }
}
