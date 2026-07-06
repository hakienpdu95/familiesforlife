<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\Queries\Hr\HeadcountQuery;
use Modules\Report\Queries\Hr\LeaveReportQuery;
use Modules\Report\Queries\Hr\RecruitmentFunnelQuery;
use Modules\Report\Queries\Hr\PerformanceReportQuery;

class HrReportApiController extends Controller
{
    public function headcount(Request $request): JsonResponse
    {
        $q = HeadcountQuery::fromRequest($request->all());
        return response()->json([
            'summary'              => $q->summary(),
            'by_status'            => $q->byStatus(),
            'by_department'        => $q->byDepartment(),
            'by_branch'            => $q->byBranch(),
            'by_employment_type'   => $q->byEmploymentType(),
            'trend'                => $q->trend(),
            'new_hires'            => $q->newHiresList(),
        ]);
    }

    public function leave(Request $request): JsonResponse
    {
        $q = LeaveReportQuery::fromRequest($request->all());
        return response()->json([
            'summary'        => $q->summary(),
            'by_type'        => $q->byType(),
            'by_status'      => $q->byStatus(),
            'by_department'  => $q->byDepartment(),
            'monthly_trend'  => $q->monthlyTrend(),
            'top_requesters' => $q->topRequesters(),
        ]);
    }

    public function recruitment(Request $request): JsonResponse
    {
        $q = RecruitmentFunnelQuery::fromRequest($request->all());
        return response()->json([
            'summary'              => $q->summary(),
            'funnel'               => $q->funnel(),
            'by_source'            => $q->bySource(),
            'open_jobs'            => $q->openJobs(),
            'monthly_applications' => $q->monthlyApplications(),
        ]);
    }

    public function performance(Request $request): JsonResponse
    {
        $q = PerformanceReportQuery::fromRequest($request->all());
        return response()->json([
            'summary'             => $q->summary(),
            'score_distribution'  => $q->scoreDistribution(),
            'by_department'       => $q->byDepartment(),
            'criteria_breakdown'  => $q->criteriaBreakdown(),
            'top_performers'      => $q->topPerformers(),
            'low_performers'      => $q->lowPerformers(),
            'period_comparison'   => $q->periodComparison(),
        ]);
    }
}
