<?php

namespace App\Http\Controllers\Backend\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ContentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardChartController extends Controller
{
    public function contentTrend(Request $request, ContentDashboardService $service): JsonResponse
    {
        abort_unless($service->isContentAudience($request->user()), 403);

        return response()->json($service->trend($request->integer('days', 30)));
    }
}
