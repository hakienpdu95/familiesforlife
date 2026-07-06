<?php

namespace Modules\Deployment\Actions;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\LogProgressData;
use Modules\Deployment\Models\DeploymentProgressLog;

class LogProgressAction
{
    use AsAction;

    public function handle(LogProgressData $data): DeploymentProgressLog
    {
        return DeploymentProgressLog::create([
            'organization_id'      => TenantContext::getOrganizationId(),
            'deployment_target_id' => $data->deployment_target_id,
            'phase'                => $data->phase,
            'percent'              => $data->percent,
            'remark'               => $data->remark,
            'logged_by'            => auth()->id(),
            'logged_at'            => now(),
        ]);
    }
}
