<?php

namespace Modules\KpiGoal\Queries;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiLeaderboardHandler
{
    public function handle(KpiLeaderboardQuery $query): Collection
    {
        $orgId = TenantContext::getOrganizationId();

        $builder = DB::table('kpi_snapshots as s')
            ->join('employees as e', 'e.id', '=', 's.employee_id')
            ->join('departments as d', 'd.id', '=', 'e.department_id')
            ->where('s.cycle_label', $query->cycle_label)
            ->where('e.organization_id', $orgId)
            ->whereNull('e.deleted_at')
            ->select([
                'e.id as employee_id',
                'e.full_name',
                'e.employee_code',
                'd.name as department_name',
                DB::raw('SUM(s.weighted_score) as kpi_raw_score'),
                DB::raw('ROUND(SUM(s.weighted_score) / 100.0 * 5, 2) as kpi_score_5'),
                DB::raw('COUNT(s.goal_id) as goal_count'),
            ])
            ->groupBy('s.employee_id', 'e.full_name', 'e.employee_code', 'd.name')
            ->orderByDesc('kpi_raw_score');

        if ($query->department_id) {
            $builder->where('e.department_id', $query->department_id);
        }

        return $builder->get();
    }
}
