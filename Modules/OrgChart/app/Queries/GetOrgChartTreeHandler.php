<?php

namespace Modules\OrgChart\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Modules\Employee\Models\Employee;
use Modules\OrgChart\Models\OrgChartConfig;

class GetOrgChartTreeHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var GetOrgChartTreeQuery $query */
        $config = $query->config;

        $employees = Employee::withoutTenant()
            ->select([
                'id', 'employee_code', 'full_name', 'avatar_url',
                'department_id', 'branch_id', 'job_title_id', 'manager_id',
                'status',
                'snap_dept_name', 'snap_branch_name', 'snap_job_title', 'snap_job_level',
            ])
            ->where('organization_id', TenantContext::getOrganizationId())
            ->where('status', 'active')
            ->when($config->scope_branch_id, fn ($q) => $q->where('branch_id', $config->scope_branch_id))
            ->orderBy('full_name')
            ->get()
            ->keyBy('id');

        // Build flat node map first
        $nodes = [];
        foreach ($employees as $emp) {
            $nodes[$emp->id] = [
                'id'            => $emp->id,
                'employee_code' => $emp->employee_code,
                'full_name'     => $emp->full_name,
                'avatar_url'    => $emp->avatar_url,
                'manager_id'    => $emp->manager_id,
                'dept_name'     => $emp->snap_dept_name,
                'branch_name'   => $emp->snap_branch_name,
                'job_title'     => $emp->snap_job_title,
                'job_level'     => $emp->snap_job_level,
                'children'      => [],
            ];
        }

        // Attach children to parents; orphans (manager not in dataset) become roots
        $roots = [];
        foreach ($nodes as $id => &$node) {
            $managerId = $node['manager_id'];
            if ($managerId && isset($nodes[$managerId])) {
                $nodes[$managerId]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        // Apply max_depth limit (0 = unlimited)
        if ($config->max_depth > 0) {
            $this->pruneDepth($roots, (int) $config->max_depth, 0);
        }

        $viewType = $config->view_type instanceof \Modules\OrgChart\Enums\OrgChartViewType
            ? $config->view_type->value
            : $config->view_type;

        $groupBy = $config->group_by instanceof \Modules\OrgChart\Enums\OrgChartGroupBy
            ? $config->group_by->value
            : $config->group_by;

        return [
            'nodes' => $roots,
            'total' => $employees->count(),
            'config' => [
                'view_type'          => $viewType,
                'group_by'           => $groupBy,
                'show_avatar'        => (bool) $config->show_avatar,
                'show_job_title'     => (bool) $config->show_job_title,
                'show_employee_code' => (bool) $config->show_employee_code,
                'show_department'    => (bool) $config->show_department,
                'show_branch'        => (bool) $config->show_branch,
                'expand_by_default'  => (bool) $config->expand_by_default,
                'max_depth'          => (int) $config->max_depth,
            ],
        ];
    }

    private function pruneDepth(array &$nodes, int $maxDepth, int $currentDepth): void
    {
        foreach ($nodes as &$node) {
            if ($currentDepth >= $maxDepth - 1) {
                $node['_has_more'] = count($node['children']) > 0;
                $node['children']  = [];
            } else {
                $this->pruneDepth($node['children'], $maxDepth, $currentDepth + 1);
            }
        }
        unset($node);
    }
}
