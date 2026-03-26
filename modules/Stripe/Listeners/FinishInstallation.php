<?php

namespace Modules\Stripe\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    public $alias = 'stripe';

    /**
     * Handle the event.
     *
     * Attaches the required permissions for Stripe settings and payment
     * management to admin roles upon module installation.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event->alias != $this->alias) {
            return;
        }

        $this->updatePermissions();
    }

    /**
     * Update permissions for the module.
     *
     * @return void
     */
    protected function updatePermissions()
    {
        // c=create, r=read, u=update, d=delete
        $this->attachPermissionsToAdminRoles([
            $this->alias . '-settings' => 'r,u',
            $this->alias . '-payments' => 'r,u,d',
        ]);
    }
}
