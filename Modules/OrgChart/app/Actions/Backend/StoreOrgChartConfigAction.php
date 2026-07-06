<?php

namespace Modules\OrgChart\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrgChart\Data\Requests\StoreOrgChartConfigData;
use Modules\OrgChart\Events\OrgChartConfigCreated;
use Modules\OrgChart\Models\OrgChartConfig;

class StoreOrgChartConfigAction
{
    use AsAction;

    public function handle(StoreOrgChartConfigData $data): OrgChartConfig
    {
        $userOrgId = auth()->user()->organization_id;
        $orgId = $userOrgId ?? $data->organization_id ?? TenantContext::getOrganizationId();

        // Unset any existing default before creating new one
        if ($data->is_default) {
            OrgChartConfig::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('is_default', 1)
                ->update(['is_default' => 0]);
        }

        $config = OrgChartConfig::create([
            'organization_id'    => $orgId,
            'created_by'         => auth()->id(),
            'name'               => $data->name,
            'view_type'          => $data->view_type->value,
            'group_by'           => $data->group_by->value,
            'scope_branch_id'    => $data->scope_branch_id,
            'show_avatar'        => $data->show_avatar,
            'show_job_title'     => $data->show_job_title,
            'show_employee_code' => $data->show_employee_code,
            'show_department'    => $data->show_department,
            'show_branch'        => $data->show_branch,
            'max_depth'          => $data->max_depth,
            'expand_by_default'  => $data->expand_by_default,
            'is_default'         => $data->is_default,
        ]);

        event(new OrgChartConfigCreated($config));

        return $config;
    }
}
