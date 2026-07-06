<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Modules\Sop\Models\SopProcess;

class SopAnalyticsController extends Controller
{
    public function dashboard(): \Illuminate\View\View
    {
        $this->authorize('viewAny', SopProcess::class);

        $orgId = TenantContext::getOrganizationId();

        // Count SOPs by status
        $statusCounts = SopProcess::where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalSops = $statusCounts->sum();

        // Expiring soon (next 7 days)
        $expiringSoon = SopProcess::where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->whereNotNull('expired_date')
            ->whereBetween('expired_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->with('owner:id,name')
            ->orderBy('expired_date')
            ->get(['id', 'uuid', 'code', 'title', 'expired_date', 'owner_id']);

        // Average steps per approved SOP
        $avgStepsRaw = DB::table('sop_steps as s')
            ->join('sop_processes as sp', 'sp.id', '=', 's.sop_id')
            ->where('sp.organization_id', $orgId)
            ->whereNull('sp.deleted_at')
            ->where('s.is_active', true)
            ->selectRaw('COUNT(s.id) as step_count')
            ->groupBy('sp.id')
            ->get();

        $avgSteps = $avgStepsRaw->isNotEmpty()
            ? round($avgStepsRaw->avg('step_count'), 1)
            : 0;

        // Duration analytics by step_type (spec section 7.4)
        $durationByType = DB::table('sop_steps as s')
            ->join('sop_processes as sp', 'sp.id', '=', 's.sop_id')
            ->where('sp.organization_id', $orgId)
            ->whereNull('sp.deleted_at')
            ->where('sp.status', 'approved')
            ->where('s.is_active', true)
            ->whereNotNull('s.duration_minutes')
            ->selectRaw('s.step_type, COUNT(*) as step_count, AVG(s.duration_minutes) as avg_duration, MAX(s.duration_minutes) as max_duration')
            ->groupBy('s.step_type')
            ->orderByDesc('avg_duration')
            ->get();

        // Step type distribution (all active steps, approved SOPs)
        $stepTypeCounts = DB::table('sop_steps as s')
            ->join('sop_processes as sp', 'sp.id', '=', 's.sop_id')
            ->where('sp.organization_id', $orgId)
            ->whereNull('sp.deleted_at')
            ->where('s.is_active', true)
            ->selectRaw('s.step_type, COUNT(*) as count')
            ->groupBy('s.step_type')
            ->orderByDesc('count')
            ->pluck('count', 'step_type');

        // Recent SOPs (last 5 created)
        $recentSops = SopProcess::where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->with('owner:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'uuid', 'code', 'title', 'status', 'created_at', 'owner_id', 'version']);

        return view('sop::sop.analytics', compact(
            'statusCounts',
            'totalSops',
            'expiringSoon',
            'avgSteps',
            'durationByType',
            'stepTypeCounts',
            'recentSops',
        ));
    }
}
