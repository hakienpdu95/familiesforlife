<?php

namespace Modules\WorkflowAutomation\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WorkflowAutomation\Core\ActionRegistry;
use Modules\WorkflowAutomation\Core\SubjectRegistry;
use Modules\WorkflowAutomation\Core\TriggerRegistry;
use Modules\WorkflowAutomation\Enums\CooldownType;
use Modules\WorkflowAutomation\Enums\OperatorType;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Models\WorkflowExecution;

class WorkflowApiController extends Controller
{
    /** GET /backend/api/workflows — Tabulator listing */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = $request->validate([
            'search'          => 'nullable|string|max:100',
            'trigger_type'    => 'nullable|string|max:64',
            'is_active'       => 'nullable|in:0,1',
            'page'            => 'nullable|integer|min:1',
            'size'            => 'nullable|integer|min:1|max:100',
            'sorters'         => 'nullable|array',
            'sorters.*.field' => 'nullable|string',
            'sorters.*.dir'   => 'nullable|in:asc,desc',
        ]);

        $page    = max(0, ((int) ($v['page'] ?? 1)) - 1);
        $size    = (int) ($v['size'] ?? 20);
        $allowed = ['name', 'trigger_type', 'is_active', 'priority', 'run_count', 'last_run_at', 'created_at'];
        $sorter  = $v['sorters'][0] ?? null;
        $sortBy  = in_array($sorter['field'] ?? '', $allowed, true) ? $sorter['field'] : 'created_at';
        $sortDir = in_array(strtolower($sorter['dir'] ?? ''), ['asc', 'desc'], true) ? $sorter['dir'] : 'desc';

        $query = Workflow::query();
        if (TenantContext::isSet()) {
            $query->where('organization_id', TenantContext::getOrganizationId());
        }
        if (!empty($v['search'])) {
            $t = '%' . $v['search'] . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $t)->orWhere('trigger_type', 'like', $t));
        }
        if (isset($v['trigger_type'])) $query->where('trigger_type', $v['trigger_type']);
        if (isset($v['is_active']))    $query->where('is_active', (bool) $v['is_active']);

        $total = $query->count();
        $rows  = $query->orderBy($sortBy, $sortDir)->offset($page * $size)->limit($size)->get();

        return response()->json([
            'data'      => $rows->map(fn ($w) => $this->normalizeWorkflow($w)),
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    /** GET /backend/api/workflows/meta — Builder UI config */
    public function meta(): \Illuminate\Http\JsonResponse
    {
        $orgId    = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
        $version  = config('workflow_automation.meta_cache_version', 1);
        $ttl      = (int) config('workflow_automation.meta_cache_ttl', 600);
        $cacheKey = 'wf:meta:v' . $version . ':' . ($orgId ?? 'global');

        return response()->json(\Cache::remember($cacheKey, $ttl, function () {
            return [
                'trigger_groups' => app(TriggerRegistry::class)->groupedByModule(),
                'action_groups'  => app(ActionRegistry::class)->groupedByModule(),
                'subjects'       => collect(app(SubjectRegistry::class)->all())
                    ->map(fn ($s) => ['label' => $s['label'], 'fields' => $s['updatableFields']])
                    ->all(),
                'operators'      => collect(OperatorType::cases())
                    ->map(fn ($op) => ['value' => $op->value, 'label' => $op->label(), 'types' => $op->applicableTypes()])
                    ->all(),
                'cooldown_types' => collect(CooldownType::cases())
                    ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])
                    ->all(),
            ];
        }));
    }

    /** GET /backend/api/workflows/executions — execution history Tabulator */
    public function executions(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = $request->validate([
            'workflow_id'  => 'nullable|integer',
            'status'       => 'nullable|integer|in:1,2,3,4,5',
            'trigger_type' => 'nullable|string|max:64',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
            'page'         => 'nullable|integer|min:1',
            'size'         => 'nullable|integer|min:1|max:100',
        ]);

        $page  = max(0, ((int) ($v['page'] ?? 1)) - 1);
        $size  = (int) ($v['size'] ?? 20);

        $query = WorkflowExecution::with('workflow:id,name');
        if (TenantContext::isSet()) {
            $query->forOrganization(TenantContext::getOrganizationId());
        }
        if (!empty($v['workflow_id']))  $query->where('workflow_id', $v['workflow_id']);
        if (!empty($v['status']))       $query->where('status', $v['status']);
        if (!empty($v['trigger_type'])) $query->where('trigger_type', $v['trigger_type']);
        if (!empty($v['date_from']))    $query->where('triggered_at', '>=', $v['date_from'] . ' 00:00:00');
        if (!empty($v['date_to']))      $query->where('triggered_at', '<=', $v['date_to'] . ' 23:59:59');

        $total = $query->count();
        $rows  = $query->orderByDesc('triggered_at')->offset($page * $size)->limit($size)->get();

        return response()->json([
            'data'      => $rows->map(fn ($e) => $this->normalizeExecution($e)),
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    /** GET /backend/api/workflows/stats */
    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId    = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
        $cacheKey = 'wf:stats:' . ($orgId ?? 'all');

        return response()->json(\Cache::remember($cacheKey, 120, function () use ($orgId) {
            $base = WorkflowExecution::when($orgId, fn ($q) => $q->where('organization_id', $orgId));

            return [
                'total_workflows'  => Workflow::when($orgId, fn ($q) => $q->where('organization_id', $orgId))->count(),
                'active_workflows' => Workflow::when($orgId, fn ($q) => $q->where('organization_id', $orgId))->where('is_active', true)->count(),
                'executions_today' => (clone $base)->whereDate('triggered_at', today())->count(),
                'by_status'        => (clone $base)->whereDate('triggered_at', today())
                    ->selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
                'recent_failures'  => (clone $base)->where('status', WorkflowStatus::Fail->value)
                    ->with('workflow:id,name')->latest('triggered_at')->limit(5)->get()
                    ->map(fn ($e) => [
                        'workflow_name' => $e->workflow?->name,
                        'triggered_at'  => $e->triggered_at,
                        'run_id'        => $e->run_id,
                    ]),
            ];
        }));
    }

    /** GET /backend/api/workflows/subject-fields/{type} — fields dropdown for UpdateSubjectExecutor */
    public function subjectFields(string $type): \Illuminate\Http\JsonResponse
    {
        $config = app(SubjectRegistry::class)->get($type);
        if (!$config) return response()->json(['fields' => []]);
        return response()->json(['fields' => $config['updatableFields']]);
    }

    private function normalizeWorkflow(Workflow $w): array
    {
        return [
            'id'              => $w->id,
            'name'            => $w->name,
            'trigger_type'    => $w->trigger_type,
            'is_active'       => $w->is_active,
            'priority'        => $w->priority,
            'run_count'       => $w->run_count,
            'last_run_at'     => $w->last_run_at,
            'last_run_status' => $w->last_run_status,
            'last_run_badge'  => $w->last_run_status_enum?->badge(),
            'last_run_label'  => $w->last_run_status_enum?->label(),
            'created_at'      => $w->created_at,
        ];
    }

    private function normalizeExecution(WorkflowExecution $e): array
    {
        return [
            'id'              => $e->id,
            'run_id'          => $e->run_id,
            'workflow_name'   => $e->workflow?->name,
            'trigger_type'    => $e->trigger_type,
            'source_module'   => $e->source_module,
            'subject_type'    => $e->subject_type,
            'subject_id'      => $e->subject_id,
            'status'          => $e->status,
            'status_label'    => $e->status_enum->label(),
            'status_badge'    => $e->status_enum->badge(),
            'skip_reason'     => $e->skip_reason,
            'steps_total'     => $e->steps_total,
            'steps_success'   => $e->steps_success,
            'steps_failed'    => $e->steps_failed,
            'steps_scheduled' => $e->steps_scheduled,
            'duration_ms'     => $e->duration_ms,
            'triggered_at'    => $e->triggered_at,
            'finished_at'     => $e->finished_at,
        ];
    }
}
