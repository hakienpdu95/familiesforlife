<?php

namespace Modules\Lead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lead\Http\Resources\LeadListResource;
use Modules\Lead\Models\Lead;
use Modules\Lead\Policies\LeadPolicy;
use Modules\Lead\Queries\GetLeadSourcesHandler;
use Modules\Lead\Queries\GetLeadSourcesQuery;
use Modules\Lead\Queries\GetPipelineStagesHandler;
use Modules\Lead\Queries\GetPipelineStagesQuery;
use Modules\Lead\Queries\LeadKanbanHandler;
use Modules\Lead\Queries\LeadKanbanQuery;
use Modules\Lead\Queries\LeadStatsHandler;
use Modules\Lead\Queries\LeadStatsQuery;
use Modules\Lead\Queries\ListLeadsHandler;
use Modules\Lead\Queries\ListLeadsQuery;

class LeadApiController extends Controller
{
    /**
     * Tabulator-compatible paginated listing — enforces permission scope automatically.
     */
    public function listing(Request $request, ListLeadsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $user   = $request->user();
        $orgId  = $this->orgId();
        $policy = app(LeadPolicy::class);

        $scopeUserId = $policy->scopeUserId($user);
        $maskContact = $policy->shouldMaskContact($user);

        $validated = $request->validate([
            'page'           => ['nullable', 'integer', 'min:1'],
            'size'           => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'         => ['nullable', 'string', 'max:200'],
            'stage_id'       => ['nullable', 'integer', 'min:1'],
            'source_id'      => ['nullable', 'integer', 'min:1'],
            'assigned_to'    => ['nullable', 'integer', 'min:1'],
            'status'         => ['nullable', 'integer'],
            'tag_ids'        => ['nullable', 'array'],
            'tag_ids.*'      => ['integer', 'min:1'],
            'min_score'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'closing_before' => ['nullable', 'date_format:Y-m-d'],
            'closing_after'  => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Parse Tabulator sort[0] payload
        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'updated_at') : 'updated_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListLeadsQuery(
            orgId:         $orgId,
            page:          max(1, (int) ($validated['page'] ?? 1)),
            perPage:       min(100, max(5, (int) ($request->input('size', 25)))),
            sortField:     $sortField,
            sortDir:       $sortDir,
            search:        $validated['search'] ?? null,
            stageId:       isset($validated['stage_id']) ? (int) $validated['stage_id'] : null,
            sourceId:      isset($validated['source_id']) ? (int) $validated['source_id'] : null,
            assignedTo:    isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
            status:        isset($validated['status']) ? (int) $validated['status'] : null,
            tagIds:        $validated['tag_ids'] ?? null,
            minScore:      isset($validated['min_score']) ? (int) $validated['min_score'] : null,
            closingBefore: $validated['closing_before'] ?? null,
            closingAfter:  $validated['closing_after'] ?? null,
            scopeUserId:   $scopeUserId,
        );

        $paginator = $handler->handle($query);
        $items     = collect($paginator->items());

        $data = LeadListResource::collection($items)->resolve();

        if ($maskContact) {
            $data = collect($data)->map(function (array $lead) {
                $lead['contact_phone']   = '***';
                $lead['contact_name']    = mb_substr($lead['contact_name'] ?? '', 0, 1) . '***';
                $lead['contact_company'] = isset($lead['contact_company']) ? '***' : null;
                return $lead;
            })->all();
        }

        return response()->json([
            'data'      => $data,
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    /**
     * Kanban board data — grouped by stage_id.
     */
    public function kanban(
        Request $request,
        LeadKanbanHandler $kanbanHandler,
        GetPipelineStagesHandler $stagesHandler,
    ): JsonResponse {
        $this->authorize('viewAny', Lead::class);

        $user   = $request->user();
        $orgId  = $this->orgId();
        $policy = app(LeadPolicy::class);

        $scopeUserId = $policy->scopeUserId($user);
        $maskContact = $policy->shouldMaskContact($user);

        $grouped = $kanbanHandler->handle(new LeadKanbanQuery($orgId, $scopeUserId));
        $stages  = $stagesHandler->handle(new GetPipelineStagesQuery($orgId));

        $board = $stages->map(function ($stage) use ($grouped, $maskContact) {
            $leads = collect($grouped[$stage->id] ?? []);

            if ($maskContact) {
                $leads = $leads->map(function ($lead) {
                    $arr                 = is_array($lead) ? $lead : $lead->toArray();
                    $arr['contact_name'] = mb_substr($arr['contact_name'] ?? '', 0, 1) . '***';
                    return $arr;
                });
            }

            return [
                'stage' => $stage,
                'leads' => $leads->values(),
                'total' => $leads->count(),
            ];
        });

        return response()->json(['data' => $board]);
    }

    /**
     * Dashboard stats — respects permission scope.
     */
    public function stats(Request $request, LeadStatsHandler $statsHandler): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $user        = $request->user();
        $orgId       = $this->orgId();
        $policy      = app(LeadPolicy::class);
        $scopeUserId = $policy->scopeUserId($user);

        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : null;
        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : null;

        return response()->json(
            $statsHandler->handle(new LeadStatsQuery($orgId, $from, $to, $scopeUserId))
        );
    }

    /**
     * Pipeline stages for the current org (for dropdowns / config).
     */
    public function stageList(GetPipelineStagesHandler $handler): JsonResponse
    {
        return response()->json($handler->handle(new GetPipelineStagesQuery($this->orgId()))->values());
    }

    /**
     * Lead sources for the current org (for dropdowns / config).
     */
    public function sourceList(GetLeadSourcesHandler $handler): JsonResponse
    {
        return response()->json($handler->handle(new GetLeadSourcesQuery($this->orgId()))->values());
    }

    /**
     * Lightweight list of users assignable as lead owner.
     * Used by TomSelect in create/edit forms.
     */
    public function assignableUsers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $orgId  = $this->resolveOrgId($request);
        $search = $request->input('q', '');

        $users = User::query()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email']);

        return response()->json($users->map(fn (User $u) => [
            'id'    => $u->id,
            'text'  => $u->name,
            'email' => $u->email,
        ]));
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function orgId(): int
    {
        return TenantContext::getOrganizationId() ?? abort(403, 'No organization context.');
    }

    /**
     * Cho phép override org qua ?organization_id= — CHỈ khi user hiện tại không khoá
     * org (super-admin). Dùng khi form tạo/sửa (Customer, Lead...) cho chọn tổ chức
     * khác org hiện tại, cần load "Người phụ trách" theo đúng org đã chọn trên form,
     * không phải org trong TenantContext của người đang thao tác.
     */
    private function resolveOrgId(Request $request): int
    {
        $requested = $request->integer('organization_id');

        if ($requested && auth()->user()->organization_id === null) {
            return $requested;
        }

        return $this->orgId();
    }
}
