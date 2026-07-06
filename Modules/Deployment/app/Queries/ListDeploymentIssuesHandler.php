<?php

namespace Modules\Deployment\Queries;

use Illuminate\Database\Eloquent\Builder;
use Modules\Deployment\Models\DeploymentIssue;

class ListDeploymentIssuesHandler
{
    public function handle(ListDeploymentIssuesQuery $query): Builder
    {
        $builder = DeploymentIssue::with(['target.targetOrganization', 'owner', 'createdBy'])
            ->orderByDesc('created_at');

        if ($query->target_id) {
            $builder->where('deployment_target_id', $query->target_id);
        }

        if ($query->project_id) {
            $builder->where('project_id', $query->project_id);
        }

        if ($query->severity) {
            $builder->where('severity', $query->severity);
        }

        if ($query->status) {
            $builder->where('status', $query->status);
        }

        if ($query->issue_type) {
            $builder->where('issue_type', $query->issue_type);
        }

        return $builder;
    }
}
