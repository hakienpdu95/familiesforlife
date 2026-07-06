<?php

namespace Modules\OrgChart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OrgChart\Models\OrgChartConfig;

class OrgChartConfigUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly OrgChartConfig $config) {}
}
