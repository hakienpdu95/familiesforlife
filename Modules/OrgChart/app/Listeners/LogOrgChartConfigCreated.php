<?php

namespace Modules\OrgChart\Listeners;

use Modules\OrgChart\Events\OrgChartConfigCreated;

class LogOrgChartConfigCreated
{
    public function handle(OrgChartConfigCreated $event): void
    {
        activity()->on($event->config)->log('org_chart_config.created');
    }
}
