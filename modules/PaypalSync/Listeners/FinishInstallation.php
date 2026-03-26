<?php

namespace Modules\PaypalSync\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    /**
     * Module alias.
     *
     * @var string
     */
    public $alias = 'paypal-sync';

    /**
     * Handle the event.
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event->alias !== $this->alias) {
            return;
        }

        $this->attachPermissionsToAdminRoles([
            $this->alias . '-settings' => 'r,u,d',
            $this->alias . '-transactions' => 'c,r,u,d',
        ]);
    }
}
