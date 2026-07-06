<?php

namespace Modules\OrgChart\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrgChart\Models\OrgChartConfig;

class DestroyOrgChartConfigAction
{
    use AsAction;

    public function handle(OrgChartConfig $config): string
    {
        $name = $config->name;
        $config->delete();
        return $name;
    }
}
