<?php

namespace App\Http\Controllers\Backend\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Lead\Enums\LeadStatus;
use Modules\Task\Enums\TaskStatus;

class DashboardChartController extends Controller
{
    // ── Task Throughput — Line chart, N days ──────────────────────────────
    public function taskThroughput(Request $request): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();
        $days  = (int) $request->input('days', 30);
        $days  = min(max($days, 7), 90);
        $from  = now()->subDays($days - 1)->startOfDay();

        // Build a full date spine so gaps show as 0
        $spine = [];
        for ($i = 0; $i < $days; $i++) {
            $spine[$from->copy()->addDays($i)->format('Y-m-d')] = ['created' => 0, 'closed' => 0];
        }

        // Tasks created per day
        $created = DB::table('tasks')
            ->where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('day')
            ->pluck('cnt', 'day');

        // Tasks closed per day
        $closed = DB::table('tasks')
            ->where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->where('status', TaskStatus::Done->value)
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $from)
            ->select(DB::raw('DATE(completed_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('day')
            ->pluck('cnt', 'day');

        foreach ($spine as $day => &$row) {
            $row['created'] = (int) ($created[$day] ?? 0);
            $row['closed']  = (int) ($closed[$day] ?? 0);
        }
        unset($row);

        $labels       = array_keys($spine);
        $createdSerie = array_column($spine, 'created');
        $closedSerie  = array_column($spine, 'closed');

        // Short label: 'dd/mm' for display
        $shortLabels = array_map(fn ($d) => substr($d, 8, 2) . '/' . substr($d, 5, 2), $labels);

        return response()->json([
            'labels'  => $shortLabels,
            'created' => $createdSerie,
            'closed'  => $closedSerie,
        ]);
    }

    // ── Lead Funnel — funnel chart by pipeline stage ───────────────────────
    public function leadFunnel(): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $stages = DB::table('lead_pipeline_stages as lps')
            ->leftJoin('leads as l', function ($join) use ($orgId) {
                $join->on('l.stage_id', '=', 'lps.id')
                     ->where('l.organization_id', $orgId)
                     ->where('l.status', LeadStatus::Active->value)
                     ->whereNull('l.deleted_at');
            })
            ->where(function ($q) use ($orgId) {
                $q->where('lps.organization_id', $orgId)
                  ->orWhere('lps.is_global', true);
            })
            ->where('lps.is_active', true)
            ->select(
                'lps.label',
                'lps.color',
                'lps.sort_order',
                'lps.is_won',
                'lps.is_lost',
                DB::raw('COUNT(l.id) as count')
            )
            ->groupBy('lps.id', 'lps.label', 'lps.color', 'lps.sort_order', 'lps.is_won', 'lps.is_lost')
            ->orderBy('lps.sort_order')
            ->get();

        return response()->json([
            'stages' => $stages->map(fn ($s) => [
                'label'   => $s->label,
                'count'   => (int) $s->count,
                'color'   => $s->color,
                'is_won'  => (bool) $s->is_won,
                'is_lost' => (bool) $s->is_lost,
            ]),
        ]);
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

    // ── Headcount by Department — donut chart ─────────────────────────────
    public function headcount(): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $rows = DB::table('employees as e')
            ->join('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.organization_id', $orgId)
            ->whereNotNull('e.department_id')
            ->whereIn('e.status', [
                EmployeeStatus::Active->value,
                EmployeeStatus::Probation->value,
                EmployeeStatus::OnLeave->value,
            ])
            ->select('d.name as department', DB::raw('COUNT(e.id) as count'))
            ->groupBy('d.id', 'd.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Employees with no department
        $noDept = DB::table('employees')
            ->where('organization_id', $orgId)
            ->whereNull('department_id')
            ->whereIn('status', [
                EmployeeStatus::Active->value,
                EmployeeStatus::Probation->value,
                EmployeeStatus::OnLeave->value,
            ])
            ->count();

        $data = $rows->map(fn ($r) => [
            'name'  => $r->department,
            'value' => (int) $r->count,
        ])->toArray();

        if ($noDept > 0) {
            $data[] = ['name' => 'Chưa phân bổ', 'value' => $noDept];
        }

        return response()->json(['departments' => $data]);
    }
}
