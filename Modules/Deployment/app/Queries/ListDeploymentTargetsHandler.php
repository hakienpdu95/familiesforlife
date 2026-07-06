<?php

namespace Modules\Deployment\Queries;

use Illuminate\Database\Eloquent\Builder;
use Modules\Deployment\Models\DeploymentTarget;

class ListDeploymentTargetsHandler
{
    public function handle(ListDeploymentTargetsQuery $query): Builder
    {
        $builder = DeploymentTarget::with(['targetOrganization', 'assignedEmployee', 'project'])
            ->where('vertical_code', $query->vertical_code)
            ->orderByDesc('created_at');

        if ($query->phase) {
            $builder->where('current_phase', $query->phase);
        }

        if ($query->project_id) {
            $builder->where('project_id', $query->project_id);
        }

        if ($query->search) {
            $builder->whereHas('targetOrganization', fn($q) =>
                $q->where('name', 'like', "%{$query->search}%")
                  ->orWhere('tax_code', 'like', "%{$query->search}%")
            );
        }

        return $builder;
    }
}
