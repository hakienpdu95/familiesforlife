<?php

namespace Modules\OrgChart\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrgChart\Data\Requests\UpdateOrgChartConfigData;
use Modules\OrgChart\Events\OrgChartConfigUpdated;
use Modules\OrgChart\Models\OrgChartConfig;

class UpdateOrgChartConfigAction
{
    use AsAction;

    public function handle(OrgChartConfig $config, UpdateOrgChartConfigData $data): OrgChartConfig
    {
        $orgId = TenantContext::getOrganizationId();

        // Unset existing default (excluding this config) before promoting this one
        if ($data->is_default && ! $config->is_default) {
            OrgChartConfig::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('is_default', 1)
                ->where('id', '!=', $config->id)
                ->update(['is_default' => 0]);
        }

        $config->update([
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

        event(new OrgChartConfigUpdated($config));

        return $config;
    }
}
