<?php

namespace Modules\Roles\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    public string $alias = 'roles';

    public function handle(Event $event): void
    {
        if ($event->alias !== $this->alias) {
            return;
        }

        $this->attachPermissionsToAdminRoles([
            'roles-roles' => 'c,r,u,d',
            'roles-assignments' => 'c,r,u,d',
        ]);
    }
}
