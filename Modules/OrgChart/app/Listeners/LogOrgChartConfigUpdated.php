<?php

namespace Modules\OrgChart\Listeners;

use Modules\OrgChart\Events\OrgChartConfigUpdated;

class LogOrgChartConfigUpdated
{
    public function handle(OrgChartConfigUpdated $event): void
    {
        activity()->on($event->config)->log('org_chart_config.updated');
    }
}
