<?php

namespace Modules\BankFeeds\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    public $alias = 'bank-feeds';

    public function handle(Event $event): void
    {
        if ($event->alias !== $this->alias) {
            return;
        }

        $this->attachPermissionsToAdminRoles([
            'bank-feeds-imports' => 'c,r,u,d',
            'bank-feeds-transactions' => 'c,r,u,d',
            'bank-feeds-rules' => 'c,r,u,d',
            'bank-feeds-matching' => 'c,r,u,d',
            'bank-feeds-reconciliation' => 'c,r,u,d',
        ]);
    }
}
