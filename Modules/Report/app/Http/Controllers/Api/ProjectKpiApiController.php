<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\Queries\ProjectKpi\ProjectOverviewQuery;
use Modules\Report\Queries\ProjectKpi\TaskProgressQuery;
use Modules\Report\Queries\ProjectKpi\KpiCycleQuery;
use Modules\Report\Queries\ProjectKpi\KpiSnapshotHistoryQuery;

class ProjectKpiApiController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $q = ProjectOverviewQuery::fromRequest($request->all());
        return response()->json([
            'summary'          => $q->summary(),
            'by_status'        => $q->byStatus(),
            'by_priority'      => $q->byPriority(),
            'by_department'    => $q->byDepartment(),
            'projects_at_risk' => $q->projectsAtRisk(),
        ]);
    }

    public function tasks(Request $request): JsonResponse
    {
        $q = TaskProgressQuery::fromRequest($request->all());
        return response()->json([
            'summary'         => $q->summary(),
            'by_priority'     => $q->byPriority(),
            'by_assignee'     => $q->byAssignee(),
            'overdue_tasks'   => $q->overdueTasks(),
            'weekly_velocity' => $q->weeklyVelocity(),
        ]);
    }

    public function kpiCycle(Request $request): JsonResponse
    {
        $q = KpiCycleQuery::fromRequest($request->all());
        return response()->json([
            'available_cycles'           => $q->availableCycles(),
            'summary'                    => $q->summary(),
            'achievement_distribution'   => $q->achievementDistribution(),
            'by_goal_type'               => $q->byGoalType(),
            'by_department'              => $q->byDepartment(),
            'top_performers'             => $q->topPerformers(),
            'at_risk'                    => $q->atRisk(),
        ]);
    }

    public function kpiSnapshot(Request $request): JsonResponse
    {
        $q = KpiSnapshotHistoryQuery::fromRequest($request->all());
        return response()->json([
            'cycles'         => $q->cycles(),
            'employee_trend' => $q->employeeTrend(),
        ]);
    }
}
