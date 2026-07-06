<?php

namespace Modules\Task\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Project\Models\Project;

class ProjectAnalyticsApiController extends Controller
{
    public function timeReport(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end'   => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        $orgId = TenantContext::getOrganizationId();

        $report = DB::table('time_logs as tl')
            ->join('employees as e', 'e.id', '=', 'tl.employee_id')
            ->where('tl.project_id', $project->id)
            ->where('tl.organization_id', $orgId)
            ->whereBetween('tl.log_date', [$request->input('start'), $request->input('end')])
            ->whereNull('tl.deleted_at')
            ->selectRaw("
                e.id AS employee_id,
                e.full_name AS employee_name,
                SUM(tl.hours) AS total_hours,
                SUM(CASE WHEN tl.is_billable = 1 THEN tl.hours ELSE 0 END) AS billable_hours,
                COUNT(DISTINCT tl.task_id) AS tasks_worked,
                COUNT(DISTINCT tl.log_date) AS days_worked
            ")
            ->groupBy('e.id', 'e.full_name')
            ->orderByDesc('total_hours')
            ->get();

        return response()->json(['data' => $report]);
    }

    public function progress(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $breakdown = DB::table('tasks')
            ->where('project_id', $project->id)
            ->where('is_leaf', true)
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->selectRaw("
                task_type,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'done'        THEN 1 ELSE 0 END) AS done,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status = 'todo'        THEN 1 ELSE 0 END) AS todo,
                SUM(CASE WHEN status = 'blocked'     THEN 1 ELSE 0 END) AS blocked,
                ROUND(AVG(progress_pct), 1) AS avg_progress
            ")
            ->groupBy('task_type')
            ->get();

        $project->refresh();

        return response()->json([
            'progress_pct' => $project->progress_pct ?? 0,
            'task_total'   => $project->task_total ?? 0,
            'task_done'    => $project->task_done ?? 0,
            'breakdown'    => $breakdown,
        ]);
    }

    public function stats(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $stats = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'done'        THEN 1 ELSE 0 END) AS done,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status = 'blocked'     THEN 1 ELSE 0 END) AS blocked,
                SUM(CASE WHEN status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled,
                SUM(estimated_hours) AS total_estimated_hours,
                SUM(logged_hours) AS total_logged_hours
            ")
            ->first();

        $overdue = DB::table('tasks')
            ->where('project_id', $project->id)
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->count();

        return response()->json([
            'data'    => $stats,
            'overdue' => $overdue,
        ]);
    }
}
