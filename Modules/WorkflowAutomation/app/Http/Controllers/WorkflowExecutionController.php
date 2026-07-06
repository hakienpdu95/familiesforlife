<?php

namespace Modules\WorkflowAutomation\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Routing\Controller;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Models\WorkflowExecution;

class WorkflowExecutionController extends Controller
{
    public function index(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        return view('workflowautomation::executions.index', compact('workflow'));
    }

    public function show(WorkflowExecution $execution): \Illuminate\View\View
    {
        if (TenantContext::isSet() && $execution->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
        $execution->load('steps');
        return view('workflowautomation::executions.show', compact('execution'));
    }

    private function authorizeForOrg(Workflow $workflow): void
    {
        if (TenantContext::isSet() && $workflow->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
    }
}
