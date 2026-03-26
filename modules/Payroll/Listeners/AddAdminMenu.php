<?php

namespace Modules\Payroll\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->dropdown(trans('payroll::general.name'), function ($sub) {
            $sub->route('payroll.pay-items.index', trans('payroll::general.pay_items'), [], 10, ['icon' => 'playlist_add']);
            $sub->route('payroll.pay-calendars.index', trans('payroll::general.pay_calendars'), [], 20, ['icon' => 'calendar_month']);
            $sub->route('payroll.payroll-runs.index', trans('payroll::general.payroll_runs'), [], 30, ['icon' => 'payments']);
            $sub->route('payroll.settings.index', trans('payroll::general.settings'), [], 40, ['icon' => 'settings']);
        }, 9, [
            'title' => trans('payroll::general.name'),
            'icon' => 'payments',
        ]);
    }
}
