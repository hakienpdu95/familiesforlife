<?php

namespace Modules\Task\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MyTasksApiController extends Controller
{
    public function index(): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $employee = DB::table('employees')
            ->where('user_id', auth()->id())
            ->where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->first(['id']);

        if (!$employee) {
            return response()->json(['data' => []]);
        }

        $tasks = DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->where('t.employee_id', $employee->id)
            ->where('t.organization_id', $orgId)
            ->whereNotIn('t.status', ['done', 'cancelled'])
            ->where('t.is_archived', false)
            ->whereNull('t.deleted_at')
            ->select([
                't.id', 't.uuid', 't.title', 't.status',
                't.priority', 't.due_date', 't.task_type',
                'p.name as project_name', 'p.status as project_status',
            ])
            ->orderByRaw("
                CASE t.priority
                    WHEN 'critical' THEN 1 WHEN 'high' THEN 2
                    WHEN 'medium'   THEN 3 ELSE 4
                END
            ")
            ->orderBy('t.due_date')
            ->limit(30)
            ->get();

        return response()->json(['data' => $tasks]);
    }
}
