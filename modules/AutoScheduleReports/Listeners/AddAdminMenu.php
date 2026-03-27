<?php

namespace Modules\AutoScheduleReports\Listeners;

use App\Events\Menu\AdminCreated as Event;

class AddAdminMenu
{
    public function handle(Event $event): void
    {
        $menu = $event->menu;

        $menu->route('auto-schedule-reports.schedules.index', trans('auto-schedule-reports::general.scheduled_reports'), [], 65, [
            'title' => trans('auto-schedule-reports::general.scheduled_reports'),
            'icon' => 'schedule_send',
        ]);
    }
}
