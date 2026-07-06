<?php

namespace Modules\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Modules\Lead\Actions\AssignLeadAction;
use Modules\Lead\Actions\ChangeLeadStageAction;
use Modules\Lead\Actions\CreateLeadAction;
use Modules\Lead\Actions\DeleteLeadAction;
use Modules\Lead\Actions\ExportLeadsAction;
use Modules\Lead\Actions\UpdateLeadAction;
use Modules\Lead\Data\Requests\StoreLeadData;
use Modules\Lead\Data\Requests\UpdateLeadData;
use Modules\Lead\Models\Lead;
use Modules\Lead\Policies\LeadPolicy;
use Modules\Lead\Queries\GetLeadHandler;
use Modules\Lead\Queries\GetLeadQuery;
use Modules\Lead\Queries\GetLeadSourcesHandler;
use Modules\Lead\Queries\GetLeadSourcesQuery;
use Modules\Lead\Queries\GetPipelineStagesHandler;
use Modules\Lead\Queries\GetPipelineStagesQuery;
use Modules\Lead\Queries\ListLeadsHandler;
use Modules\Lead\Queries\ListLeadsQuery;
use App\Models\Province;
use App\Shared\Tenancy\Models\Organization;
use Modules\Lead\Queries\ListTagsHandler;
use Modules\Lead\Queries\ListTagsQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Lead::class, 'lead');
    }

    public function index(
        Request $request,
        GetPipelineStagesHandler $stagesHandler,
        GetLeadSourcesHandler $sourcesHandler,
    ): View {
        $orgId   = $this->orgId();
        $user    = $request->user();
        $policy  = app(LeadPolicy::class);

        $stages      = $stagesHandler->handle(new GetPipelineStagesQuery($orgId));
        $sources     = $sourcesHandler->handle(new GetLeadSourcesQuery($orgId));
        $maskContact = $policy->shouldMaskContact($user);

        return view('lead::leads.index', compact('stages', 'sources', 'maskContact'));
    }

    public function create(
        GetPipelineStagesHandler $stagesHandler,
        GetLeadSourcesHandler $sourcesHandler,
        ListTagsHandler $tagsHandler,
    ): View {
        $orgId = $this->orgId();

        $stages    = $stagesHandler->handle(new GetPipelineStagesQuery($orgId));
        $sources   = $sourcesHandler->handle(new GetLeadSourcesQuery($orgId));
        $tags      = $tagsHandler->handle(new ListTagsQuery($orgId));
        $provinces = Province::orderBy('name')->get(['province_code', 'name']);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('lead::leads.create', compact('stages', 'sources', 'tags', 'provinces', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, CreateLeadAction $action): RedirectResponse
    {
        $data = StoreLeadData::validateAndCreate($request->all());
        $lead = $action->handle($data, $this->orgId());

        return redirect()->route('lead.show', $lead)
            ->with('success', 'Đã tạo lead thành công.');
    }

    public function show(
        Lead $lead,
        GetLeadHandler $handler,
        GetPipelineStagesHandler $stagesHandler,
        GetLeadSourcesHandler $sourcesHandler,
        Request $request,
    ): View {
        $orgId       = $this->orgId();
        $policy      = app(LeadPolicy::class);
        $maskContact = $policy->shouldMaskContact($request->user());

        $lead    = $handler->handle(new GetLeadQuery($lead));
        $stages  = $stagesHandler->handle(new GetPipelineStagesQuery($orgId));
        $sources = $sourcesHandler->handle(new GetLeadSourcesQuery($orgId));

        $surveyResponseUrl = null;
        if ($lead->survey_response_id && Route::has('backend.surveys.responses.show')) {
            $surveyResponse = \Modules\Survey\Models\SurveyResponse::find($lead->survey_response_id);
            if ($surveyResponse) {
                $surveyResponseUrl = route('backend.surveys.responses.show', [
                    $surveyResponse->survey_id,
                    $surveyResponse->id,
                ]);
            }
        }

        // Assessment result (nếu org đã bật lead assessment)
        $assessmentResult = null;
        if (class_exists(\Modules\Assessment\Models\AssessmentResult::class) && $lead->getAssessmentCode()) {
            $assessmentResult = \Modules\Assessment\Models\AssessmentResult
                ::forSubject(Lead::class, $lead->id)
                ->with(['domainScores', 'classification', 'painPoints', 'recommendations', 'roadmapPhases.phase'])
                ->first();
        }

        return view('lead::leads.show', compact(
            'lead', 'stages', 'sources', 'maskContact', 'surveyResponseUrl', 'assessmentResult'
        ));
    }

    public function edit(
        Lead $lead,
        GetPipelineStagesHandler $stagesHandler,
        GetLeadSourcesHandler $sourcesHandler,
    ): View {
        $orgId = $this->orgId();

        $stages  = $stagesHandler->handle(new GetPipelineStagesQuery($orgId));
        $sources = $sourcesHandler->handle(new GetLeadSourcesQuery($orgId));

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('lead::leads.edit', compact('lead', 'stages', 'sources', 'organizations', 'orgLocked'));
    }

    public function update(
        Request $request,
        Lead $lead,
        UpdateLeadAction $action,
    ): RedirectResponse {
        $data = UpdateLeadData::validateAndCreate($request->all());
        $action->handle($lead, $data);

        return redirect()->route('lead.show', $lead)
            ->with('success', 'Đã cập nhật lead.');
    }

    public function destroy(Lead $lead, DeleteLeadAction $action): RedirectResponse
    {
        $action->handle($lead);

        return redirect()->route('lead.index')
            ->with('success', 'Đã xóa lead.');
    }

    public function export(
        Request $request,
        ListLeadsHandler $listHandler,
        ExportLeadsAction $action,
    ): StreamedResponse {
        $this->authorize('export', Lead::class);

        $orgId  = $this->orgId();
        $user   = $request->user();
        $policy = app(LeadPolicy::class);

        $query = new ListLeadsQuery(
            orgId:       $orgId,
            page:        1,
            perPage:     10000,
            sortField:   'created_at',
            sortDir:     'desc',
            scopeUserId: $policy->scopeUserId($user),
        );

        $paginator = $listHandler->handle($query);
        $leads     = $paginator->getCollection()->load(['stage', 'source', 'assignee']);

        return $action->handle($leads, $policy->shouldMaskContact($user));
    }

    // ── AJAX actions (not standard resource actions — manual authorization) ───

    public function changeStage(
        Request $request,
        Lead $lead,
        ChangeLeadStageAction $action,
    ): JsonResponse {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'stage_id' => 'required|integer|min:1',
            'note'     => 'nullable|string|max:1000',
        ]);

        $updated = $action->handle($lead, (int) $validated['stage_id'], $validated['note'] ?? null);

        return response()->json([
            'ok'     => true,
            'status' => $updated->status?->label(),
            'stage'  => $updated->stage?->label,
        ]);
    }

    public function assign(
        Request $request,
        Lead $lead,
        AssignLeadAction $action,
    ): JsonResponse {
        $this->authorize('assign', $lead);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|min:1',
        ]);

        $updated = $action->handle($lead, isset($validated['user_id']) ? (int) $validated['user_id'] : null);

        return response()->json([
            'ok'       => true,
            'assignee' => $updated->assignee?->name,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function orgId(): int
    {
        return TenantContext::getOrganizationId() ?? abort(403, 'No organization context.');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}
