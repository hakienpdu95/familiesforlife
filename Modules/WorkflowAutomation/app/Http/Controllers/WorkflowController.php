<?php

namespace Modules\WorkflowAutomation\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WorkflowAutomation\Actions\ExecuteWorkflowAction;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Services\WorkflowBuilderService;

class WorkflowController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('workflowautomation::workflows.index');
    }

    public function show(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        return view('workflowautomation::workflows.show', compact('workflow'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('workflowautomation::workflows.create');
    }

    public function edit(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        $workflow->load(['triggerParams', 'conditions', 'steps.headers']);
        return view('workflowautomation::workflows.edit', compact('workflow'));
    }

    public function store(Request $request, WorkflowBuilderService $builder): \Illuminate\Http\RedirectResponse
    {
        $workflow = $builder->createFromRequest($request);
        return redirect()->route('workflows.show', $workflow)->with('success', 'Workflow đã tạo.');
    }

    public function update(Request $request, Workflow $workflow, WorkflowBuilderService $builder): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeForOrg($workflow);
        $builder->updateFromRequest($request, $workflow);
        return redirect()->route('workflows.show', $workflow)->with('success', 'Đã cập nhật.');
    }

    public function destroy(Workflow $workflow): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeForOrg($workflow);
        $workflow->delete();
        return redirect()->route('workflows.index')->with('success', 'Đã xóa.');
    }

    public function toggle(Workflow $workflow): \Illuminate\Http\JsonResponse
    {
        $this->authorizeForOrg($workflow);
        $workflow->update(['is_active' => !$workflow->is_active]);
        return response()->json(['is_active' => $workflow->is_active]);
    }

    public function manualRun(Workflow $workflow): \Illuminate\Http\JsonResponse
    {
        $this->authorizeForOrg($workflow);
        $runId   = (string) \Str::uuid();
        $payload = new TriggerPayload(
            triggerType:    'manual',
            sourceModule:   'Core',
            organizationId: TenantContext::getOrganizationId(),
            actorId:        auth()->id(),
            actorEmail:     auth()->user()?->email,
            actorName:      auth()->user()?->name,
            actorRole:      null,
            subjectType:    null,
            subjectId:      null,
            subjectLabel:   null,
            requestId:      request()->header('X-Request-Id', $runId),
        );
        ExecuteWorkflowAction::dispatch($workflow->id, $payload, $runId)->onQueue('workflows');
        return response()->json(['queued' => true, 'run_id' => $runId]);
    }

    private function authorizeForOrg(Workflow $workflow): void
    {
        if (TenantContext::isSet() && $workflow->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
    }
}
