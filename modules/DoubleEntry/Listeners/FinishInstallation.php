<?php

namespace Modules\DoubleEntry\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    public $alias = 'double-entry';

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event->alias != $this->alias) {
            return;
        }

        $this->updatePermissions();
    }

    protected function updatePermissions()
    {
        // c=create, r=read, u=update, d=delete
        $this->attachPermissionsToAdminRoles([
            'double-entry-accounts' => 'c,r,u,d',
            'double-entry-account-defaults' => 'r,u',
            'double-entry-journals' => 'c,r,u,d',
            'double-entry-general-ledger' => 'r',
            'double-entry-trial-balance' => 'r',
            'double-entry-balance-sheet' => 'r',
            'double-entry-profit-loss' => 'r',
        ]);
    }
}
