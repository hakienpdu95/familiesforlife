<?php

namespace Modules\Subscription\Features\Subscribe\Listeners;

use Modules\Organization\Events\OrganizationCreated;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;

class AutoSubscribeOnOrgCreated
{
    public function handle(OrganizationCreated $event): void
    {
        SubscribeOrganizationAction::subscribeToDefaultPlan($event->organization);
    }
}
