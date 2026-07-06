<?php

namespace Modules\OrgChart\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\OrgChart\Models\OrgChartConfig;

class ListOrgChartConfigsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'view_type', 'group_by', 'is_default', 'max_depth', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOrgChartConfigsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'is_default';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = OrgChartConfig::withoutTenant()
            ->select('org_chart_configs.*')
            ->with(['scopeBranch:id,name,code'])
            ->where('org_chart_configs.organization_id', TenantContext::getOrganizationId());

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('org_chart_configs.name', 'like', $term);
            });
        }

        if ($query->viewType !== null && $query->viewType !== '') {
            $q->where('org_chart_configs.view_type', $query->viewType);
        }

        if ($query->groupBy !== null && $query->groupBy !== '') {
            $q->where('org_chart_configs.group_by', $query->groupBy);
        }

        // Default config always first
        $q->orderByDesc('org_chart_configs.is_default')
          ->orderBy('org_chart_configs.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
