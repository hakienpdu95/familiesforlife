<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\Queries\Sales\PipelineFunnelQuery;
use Modules\Report\Queries\Sales\ConversionRateQuery;
use Modules\Report\Queries\Sales\SalesActivityQuery;

class SalesReportApiController extends Controller
{
    public function pipeline(Request $request): JsonResponse
    {
        $q = PipelineFunnelQuery::fromRequest($request->all());
        return response()->json([
            'summary'          => $q->summary(),
            'funnel'           => $q->funnel(),
            'by_source'        => $q->bySource(),
            'by_assignee'      => $q->byAssignee(),
            'win_loss_summary' => $q->winLossSummary(),
            'trend'            => $q->trend(),
        ]);
    }

    public function conversion(Request $request): JsonResponse
    {
        $q = ConversionRateQuery::fromRequest($request->all());
        return response()->json([
            'overall'        => $q->overall(),
            'by_source'      => $q->bySource(),
            'by_score_band'  => $q->byScoreBand(),
            'monthly_cohort' => $q->monthlyCohort(),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $q = SalesActivityQuery::fromRequest($request->all());
        return response()->json([
            'summary'      => $q->summary(),
            'by_assignee'  => $q->byAssignee(),
            'by_type'      => $q->byType(),
            'by_day'       => $q->byDay(),
            'response_time'=> $q->leadResponseTime(),
        ]);
    }
}
