<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcOffer;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RcApplication::class);

        return view('recruitment::analytics.index');
    }

    public function overview(): JsonResponse
    {
        $this->authorize('viewAny', RcApplication::class);

        $orgId = TenantContext::getOrganizationId();

        $totalCandidates  = DB::table('rc_candidates')->where('org_id', $orgId)->count();
        $activeCandidates = DB::table('rc_candidates')->where('org_id', $orgId)->where('status', 'active')->count();
        $totalApps        = DB::table('rc_applications')->where('org_id', $orgId)->count();
        $activeApps       = DB::table('rc_applications')->where('org_id', $orgId)->where('status', 'active')->count();
        $hiredThisMonth   = DB::table('rc_applications')
            ->where('org_id', $orgId)
            ->where('status', 'hired')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $offersAccepted = DB::table('rc_offers')
            ->join('rc_applications', 'rc_applications.id', '=', 'rc_offers.application_id')
            ->where('rc_applications.org_id', $orgId)
            ->where('rc_offers.status', 'accepted')
            ->count();

        return response()->json([
            'total_candidates'  => $totalCandidates,
            'active_candidates' => $activeCandidates,
            'total_applications'=> $totalApps,
            'active_applications'=> $activeApps,
            'hired_this_month'  => $hiredThisMonth,
            'offers_accepted'   => $offersAccepted,
        ]);
    }

    public function funnel(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RcApplication::class);

        $orgId       = TenantContext::getOrganizationId();
        $jpPostUuid  = $request->query('jp_job_post_uuid');

        $query = DB::table('rc_pipeline_stages as ps')
            ->leftJoin('rc_application_stage_logs as sl', 'sl.stage_id', '=', 'ps.id')
            ->leftJoin('rc_applications as a', function ($join) use ($jpPostUuid) {
                $join->on('a.id', '=', 'sl.application_id');
                if ($jpPostUuid) {
                    $join->where('a.jp_job_post_id', $jpPostUuid);
                }
            })
            ->where('ps.org_id', $orgId)
            ->where('ps.is_active', true)
            ->groupBy('ps.id', 'ps.name', 'ps.sort_order', 'ps.color_hex')
            ->orderBy('ps.sort_order')
            ->select([
                'ps.id',
                'ps.name',
                'ps.sort_order',
                'ps.color_hex',
                DB::raw('COUNT(DISTINCT sl.application_id) as total'),
                DB::raw('SUM(CASE WHEN sl.result = \'passed\' THEN 1 ELSE 0 END) as passed'),
            ]);

        $rows = $query->get()->map(function ($r) {
            $r->pass_rate = $r->total > 0
                ? round($r->passed * 100 / $r->total, 1)
                : null;
            return $r;
        });

        return response()->json($rows);
    }

    public function timeToHire(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RcApplication::class);

        $orgId = TenantContext::getOrganizationId();
        $from  = $request->query('from', now()->subMonths(6)->toDateString());
        $to    = $request->query('to', now()->toDateString());

        $rows = DB::table('rc_offers as o')
            ->join('rc_applications as a', 'a.id', '=', 'o.application_id')
            ->where('a.org_id', $orgId)
            ->where('o.status', 'accepted')
            ->whereBetween('o.responded_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotNull('o.responded_at')
            ->whereNotNull('a.applied_at')
            ->select([
                'a.jp_job_post_id',
                DB::raw('COUNT(o.id) as hired'),
                DB::raw('ROUND(AVG(CAST((JULIANDAY(o.responded_at) - JULIANDAY(a.applied_at)) AS REAL)), 1) as avg_days_to_hire'),
            ])
            ->groupBy('a.jp_job_post_id')
            ->orderBy('avg_days_to_hire')
            ->get();

        return response()->json($rows);
    }

    public function source(): JsonResponse
    {
        $this->authorize('viewAny', RcApplication::class);

        $orgId = TenantContext::getOrganizationId();

        $rows = DB::table('rc_applications as a')
            ->where('a.org_id', $orgId)
            ->groupBy('a.apply_source')
            ->select([
                'a.apply_source as source',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN a.status = \'hired\' THEN 1 ELSE 0 END) as hired'),
                DB::raw('SUM(CASE WHEN a.status = \'rejected\' THEN 1 ELSE 0 END) as rejected'),
            ])
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                $r->conversion_rate = $r->total > 0
                    ? round($r->hired * 100 / $r->total, 1)
                    : 0;
                return $r;
            });

        return response()->json($rows);
    }
}
