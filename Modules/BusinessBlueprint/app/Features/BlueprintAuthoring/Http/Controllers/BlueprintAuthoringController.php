<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertAiCapabilityAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertAnalyticAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertCapabilityAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertChecklistAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertDeploymentRoleAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertOutcomeAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertPhaseAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertResourceLinkAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertSidebarItemAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertWorkflowAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AiCapabilityData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AnalyticData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\CapabilityData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ChecklistData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\DeploymentRoleData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\OutcomeData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\PhaseData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ResourceLinkData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\SidebarItemData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\WorkflowData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Exceptions\BlueprintVersionLockedException;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\GetBlueprintTreeHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\GetBlueprintTreeQuery;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintIntegrityHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintIntegrityQuery;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintReadinessHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintReadinessQuery;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessBlueprint\Models\BlueprintAiCapability;
use Modules\BusinessBlueprint\Models\BlueprintAnalytic;
use Modules\BusinessBlueprint\Models\BlueprintCapability;
use Modules\BusinessBlueprint\Models\BlueprintChecklist;
use Modules\BusinessBlueprint\Models\BlueprintDeploymentRole;
use Modules\BusinessBlueprint\Models\BlueprintOutcome;
use Modules\BusinessBlueprint\Models\BlueprintPhase;
use Modules\BusinessBlueprint\Models\BlueprintResourceLink;
use Modules\BusinessBlueprint\Models\BlueprintSidebarItem;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\BusinessBlueprint\Models\BlueprintWorkflow;

class BlueprintAuthoringController extends Controller
{
    // ── Tree / Validate ─────────────────────────────────────────────────

    public function tree(Blueprint $blueprint, BlueprintVersion $version, GetBlueprintTreeHandler $handler): View
    {
        $version = $handler->handle(new GetBlueprintTreeQuery($version->id));

        return view('businessblueprint::admin.blueprint-authoring.tree', compact('blueprint', 'version'));
    }

    public function validateIntegrity(BlueprintVersion $version, ValidateBlueprintIntegrityHandler $handler): JsonResponse
    {
        return response()->json($handler->handle(new ValidateBlueprintIntegrityQuery($version->id)));
    }

    public function readiness(BlueprintVersion $version, ValidateBlueprintReadinessHandler $handler): JsonResponse
    {
        return response()->json($handler->handle(new ValidateBlueprintReadinessQuery($version->id)));
    }

    // ── Outcomes ─────────────────────────────────────────────────────────

    public function storeOutcome(Request $request, UpsertOutcomeAction $action): RedirectResponse
    {
        $data = OutcomeData::from($request->validate($this->outcomeRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Outcome.');
    }

    public function updateOutcome(Request $request, BlueprintOutcome $outcome, UpsertOutcomeAction $action): RedirectResponse
    {
        $data = OutcomeData::from($request->validate($this->outcomeRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $outcome), 'Đã cập nhật Outcome.');
    }

    public function destroyOutcome(BlueprintOutcome $outcome): RedirectResponse
    {
        return $this->handleUpsert(function () use ($outcome) {
            $this->guardVersionDraft($outcome->blueprint_version_id);
            $outcome->delete();
        }, 'Đã xóa Outcome.');
    }

    // ── Capabilities ─────────────────────────────────────────────────────

    public function storeCapability(Request $request, UpsertCapabilityAction $action): RedirectResponse
    {
        $data = CapabilityData::from($request->validate($this->capabilityRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Capability.');
    }

    public function updateCapability(Request $request, BlueprintCapability $capability, UpsertCapabilityAction $action): RedirectResponse
    {
        $data = CapabilityData::from($request->validate($this->capabilityRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $capability), 'Đã cập nhật Capability.');
    }

    public function destroyCapability(BlueprintCapability $capability): RedirectResponse
    {
        return $this->handleUpsert(function () use ($capability) {
            $this->guardVersionDraft($capability->blueprint_version_id);
            $capability->delete();
        }, 'Đã xóa Capability.');
    }

    // ── Workflows ────────────────────────────────────────────────────────

    public function storeWorkflow(Request $request, UpsertWorkflowAction $action): RedirectResponse
    {
        $data = WorkflowData::from($request->validate($this->workflowRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Workflow.');
    }

    public function updateWorkflow(Request $request, BlueprintWorkflow $workflow, UpsertWorkflowAction $action): RedirectResponse
    {
        $data = WorkflowData::from($request->validate($this->workflowRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $workflow), 'Đã cập nhật Workflow.');
    }

    public function destroyWorkflow(BlueprintWorkflow $workflow): RedirectResponse
    {
        return $this->handleUpsert(function () use ($workflow) {
            $this->guardVersionDraft($workflow->blueprint_version_id);
            $workflow->delete();
        }, 'Đã xóa Workflow.');
    }

    // ── Phases ───────────────────────────────────────────────────────────

    public function storePhase(Request $request, UpsertPhaseAction $action): RedirectResponse
    {
        $data = PhaseData::from($request->validate($this->phaseRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Phase.');
    }

    public function updatePhase(Request $request, BlueprintPhase $phase, UpsertPhaseAction $action): RedirectResponse
    {
        $data = PhaseData::from($request->validate($this->phaseRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $phase), 'Đã cập nhật Phase.');
    }

    public function destroyPhase(BlueprintPhase $phase): RedirectResponse
    {
        return $this->handleUpsert(function () use ($phase) {
            $this->guardVersionDraft($phase->workflow->blueprint_version_id);
            $phase->delete();
        }, 'Đã xóa Phase.');
    }

    // ── Checklists ───────────────────────────────────────────────────────

    public function storeChecklist(Request $request, UpsertChecklistAction $action): RedirectResponse
    {
        $data = ChecklistData::from($request->validate($this->checklistRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Checklist.');
    }

    public function updateChecklist(Request $request, BlueprintChecklist $checklist, UpsertChecklistAction $action): RedirectResponse
    {
        $data = ChecklistData::from($request->validate($this->checklistRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $checklist), 'Đã cập nhật Checklist.');
    }

    public function destroyChecklist(BlueprintChecklist $checklist): RedirectResponse
    {
        return $this->handleUpsert(function () use ($checklist) {
            $this->guardVersionDraft($checklist->phase->workflow->blueprint_version_id);
            $checklist->delete();
        }, 'Đã xóa Checklist.');
    }

    // ── Resource Links ───────────────────────────────────────────────────

    public function storeResourceLink(Request $request, UpsertResourceLinkAction $action): RedirectResponse
    {
        $data = ResourceLinkData::from($request->validate($this->resourceLinkRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Resource.');
    }

    public function updateResourceLink(Request $request, BlueprintResourceLink $resourceLink, UpsertResourceLinkAction $action): RedirectResponse
    {
        $data = ResourceLinkData::from($request->validate($this->resourceLinkRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $resourceLink), 'Đã cập nhật Resource.');
    }

    public function destroyResourceLink(BlueprintResourceLink $resourceLink): RedirectResponse
    {
        return $this->handleUpsert(function () use ($resourceLink) {
            $this->guardVersionDraft($resourceLink->blueprint_version_id);
            $resourceLink->delete();
        }, 'Đã xóa Resource.');
    }

    // ── AI Capabilities ──────────────────────────────────────────────────

    public function storeAiCapability(Request $request, UpsertAiCapabilityAction $action): RedirectResponse
    {
        $data = AiCapabilityData::from($request->validate($this->aiCapabilityRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm AI Capability.');
    }

    public function updateAiCapability(Request $request, BlueprintAiCapability $aiCapability, UpsertAiCapabilityAction $action): RedirectResponse
    {
        $data = AiCapabilityData::from($request->validate($this->aiCapabilityRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $aiCapability), 'Đã cập nhật AI Capability.');
    }

    public function destroyAiCapability(BlueprintAiCapability $aiCapability): RedirectResponse
    {
        return $this->handleUpsert(function () use ($aiCapability) {
            $this->guardVersionDraft($aiCapability->blueprint_version_id);
            $aiCapability->delete();
        }, 'Đã xóa AI Capability.');
    }

    // ── Analytics ────────────────────────────────────────────────────────

    public function storeAnalytic(Request $request, UpsertAnalyticAction $action): RedirectResponse
    {
        $data = AnalyticData::from($request->validate($this->analyticRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Analytic.');
    }

    public function updateAnalytic(Request $request, BlueprintAnalytic $analytic, UpsertAnalyticAction $action): RedirectResponse
    {
        $data = AnalyticData::from($request->validate($this->analyticRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $analytic), 'Đã cập nhật Analytic.');
    }

    public function destroyAnalytic(BlueprintAnalytic $analytic): RedirectResponse
    {
        return $this->handleUpsert(function () use ($analytic) {
            $this->guardVersionDraft($analytic->blueprint_version_id);
            $analytic->delete();
        }, 'Đã xóa Analytic.');
    }

    // ── Deployment Roles ─────────────────────────────────────────────────

    public function storeDeploymentRole(Request $request, UpsertDeploymentRoleAction $action): RedirectResponse
    {
        $data = DeploymentRoleData::from($request->validate($this->deploymentRoleRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm Role.');
    }

    public function updateDeploymentRole(Request $request, BlueprintDeploymentRole $role, UpsertDeploymentRoleAction $action): RedirectResponse
    {
        $data = DeploymentRoleData::from($request->validate($this->deploymentRoleRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $role), 'Đã cập nhật Role.');
    }

    public function destroyDeploymentRole(BlueprintDeploymentRole $role): RedirectResponse
    {
        return $this->handleUpsert(function () use ($role) {
            $this->guardVersionDraft($role->blueprint_version_id);
            $role->delete();
        }, 'Đã xóa Role.');
    }

    // ── Sidebar Items ────────────────────────────────────────────────────

    public function storeSidebarItem(Request $request, UpsertSidebarItemAction $action): RedirectResponse
    {
        $data = SidebarItemData::from($request->validate($this->sidebarItemRules()));

        return $this->handleUpsert(fn () => $action->handle($data), 'Đã thêm mục sidebar.');
    }

    public function updateSidebarItem(Request $request, BlueprintSidebarItem $sidebarItem, UpsertSidebarItemAction $action): RedirectResponse
    {
        $data = SidebarItemData::from($request->validate($this->sidebarItemRules()));

        return $this->handleUpsert(fn () => $action->handle($data, $sidebarItem), 'Đã cập nhật mục sidebar.');
    }

    public function destroySidebarItem(BlueprintSidebarItem $sidebarItem): RedirectResponse
    {
        return $this->handleUpsert(function () use ($sidebarItem) {
            $this->guardVersionDraft($sidebarItem->blueprint_version_id);
            $sidebarItem->delete();
        }, 'Đã xóa mục sidebar.');
    }

    // ── Validation rule sets ─────────────────────────────────────────────

    private function outcomeRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'code'                  => 'required|string|max:50',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'success_metric'        => 'nullable|string|max:255',
            'sort_order'            => 'nullable|integer|min:0',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function capabilityRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'outcome_id'            => 'nullable|integer|exists:blueprint_outcomes,id',
            'code'                  => 'required|string|max:50',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'capability_type'       => 'nullable|string|max:50',
            'sort_order'            => 'nullable|integer|min:0',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function workflowRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'capability_id'         => 'nullable|integer|exists:blueprint_capabilities,id',
            'code'                  => 'required|string|max:50',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'sort_order'            => 'nullable|integer|min:0',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function phaseRules(): array
    {
        return [
            'workflow_id'                  => 'required|integer|exists:blueprint_workflows,id',
            'code'                          => 'required|string|max:50',
            'name'                          => 'required|string|max:255',
            'description'                   => 'nullable|string',
            'sort_order'                    => 'nullable|integer|min:0',
            'entry_condition'               => 'nullable|string',
            'exit_condition'                => 'nullable|string',
            'is_initial'                    => 'boolean',
            'auto_assign_data_collection'   => 'boolean',
            'status'                        => 'nullable|string|in:active,inactive',
        ];
    }

    private function checklistRules(): array
    {
        return [
            'phase_id'             => 'required|integer|exists:blueprint_phases,id',
            'code'                  => 'required|string|max:50',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'input_description'    => 'nullable|string',
            'action_description'   => 'nullable|string',
            'output_description'  => 'nullable|string',
            'required'              => 'boolean',
            'default_priority'      => 'nullable|string|in:low,normal,high',
            'estimated_hours'       => 'nullable|numeric|min:0',
            'need_approval'         => 'boolean',
            'sort_order'            => 'nullable|integer|min:0',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function resourceLinkRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'checklist_id'          => 'nullable|integer|exists:blueprint_checklists,id',
            'resource_type'         => 'required|string|in:sop,knowledge,dataset,template',
            'resource_id'           => 'required|integer|min:1',
            'is_required'           => 'boolean',
            'sort_order'            => 'nullable|integer|min:0',
        ];
    }

    private function aiCapabilityRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'checklist_id'          => 'nullable|integer|exists:blueprint_checklists,id',
            'capability_code'       => 'required|string|max:100',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'ai_agent_id'           => 'nullable|integer|exists:ai_agents,id',
            'ai_prompt_id'          => 'nullable|integer|exists:ai_prompts,id',
            'trigger_event'         => 'nullable|string|max:100',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function analyticRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'metric_code'           => 'required|string|max:100',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'metric_type'           => 'nullable|string|max:50',
            'formula'               => 'nullable|string',
            'source_type'           => 'nullable|string|max:50',
            'status'                => 'nullable|string|in:active,inactive',
        ];
    }

    private function deploymentRoleRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'role_code'             => 'required|string|max:100',
            'role_name'             => 'required|string|max:255',
            'description'           => 'nullable|string',
            'sort_order'            => 'nullable|integer|min:0',
        ];
    }

    private function sidebarItemRules(): array
    {
        return [
            'blueprint_version_id' => 'required|integer|exists:blueprint_versions,id',
            'parent_id'             => 'nullable|integer|exists:blueprint_sidebar_items,id',
            'module_key'            => 'required|string|max:100',
            'label'                 => 'required|string|max:255',
            'icon'                  => 'nullable|string|max:100',
            'sort_order'            => 'nullable|integer|min:0',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function handleUpsert(\Closure $fn, string $successMessage): RedirectResponse
    {
        try {
            $fn();
        } catch (BlueprintVersionLockedException $e) {
            return back()->withErrors(['blueprint' => $e->getMessage()]);
        }

        return back()->with('success', $successMessage);
    }

    /** Chặn sửa cây khi version không còn ở trạng thái mutable (draft/in_design/...). */
    private function guardVersionDraft(int $blueprintVersionId): void
    {
        $status = BlueprintVersion::whereKey($blueprintVersionId)->value('status');
        if (BlueprintVersionStatus::from($status)->isImmutable()) {
            throw new BlueprintVersionLockedException($status);
        }
    }
}
