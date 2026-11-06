<?php

namespace App\Http\Controllers\Backend\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ContentDashboardService;
use App\Shared\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardChartController extends Controller
{
    public function contentTrend(Request $request, ContentDashboardService $service): JsonResponse
    {
        abort_unless($service->isContentAudience($request->user()), 403);

        return response()->json($service->trend($request->integer('days', 30)));
    }

    // ── Workflow Health — stacked bar, N days ─────────────────────────────
    public function workflowHealth(Request $request): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();
        $days  = (int) $request->input('days', 14);
        $days  = min(max($days, 7), 30);
        $from  = now()->subDays($days - 1)->startOfDay();

        // Spine: all dates with zero counts per status
        $statuses = [1 => 'pass', 3 => 'fail', 6 => 'halted', 7 => 'waiting'];
        $spine    = [];
        for ($i = 0; $i < $days; $i++) {
            $day          = $from->copy()->addDays($i)->format('Y-m-d');
            $spine[$day]  = array_fill_keys(array_values($statuses), 0);
        }

        $rows = DB::table('workflow_executions')
            ->where('organization_id', $orgId)
            ->whereDate('created_at', '>=', $from)
            ->whereIn('status', array_keys($statuses))
            ->select(
                DB::raw('DATE(created_at) as day'),
                'status',
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('day', 'status')
            ->get();

        foreach ($rows as $row) {
            $key = $statuses[$row->status] ?? null;
            if ($key && isset($spine[$row->day])) {
                $spine[$row->day][$key] = (int) $row->cnt;
            }
        }

        $shortLabels = array_map(fn ($d) => substr($d, 8, 2) . '/' . substr($d, 5, 2), array_keys($spine));

        return response()->json([
            'labels'  => $shortLabels,
            'pass'    => array_column($spine, 'pass'),
            'fail'    => array_column($spine, 'fail'),
            'halted'  => array_column($spine, 'halted'),
            'waiting' => array_column($spine, 'waiting'),
        ]);
    }
}
