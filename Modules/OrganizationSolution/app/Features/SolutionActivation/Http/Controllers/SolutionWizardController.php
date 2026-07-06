<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureAiAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureCapabilitiesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureChecklistsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureDashboardAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureResourcesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureWorkflowsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\MapRolesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\MarkSolutionReadyAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ValidatePreDeployHandler;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ValidatePreDeployQuery;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class SolutionWizardController extends Controller
{
    // ── Bước 3: Capabilities ─────────────────────────────────────────────

    public function showCapabilities(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.capabilities', 'capabilityConfigs');

        return view('organizationsolution::wizard.capabilities', compact('organizationSolution'));
    }

    public function capabilities(Request $request, OrganizationSolution $organizationSolution, ConfigureCapabilitiesAction $action): RedirectResponse
    {
        $action->handle($organizationSolution, $this->normalizedItems($request, 'blueprint_capability_id'));

        return redirect()->route('organization_solutions.wizard.workflows.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình Capabilities.');
    }

    // ── Bước 4: Workflows ────────────────────────────────────────────────

    public function showWorkflows(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.capabilities.workflows', 'workflowConfigs');

        return view('organizationsolution::wizard.workflows', compact('organizationSolution'));
    }

    public function workflows(Request $request, OrganizationSolution $organizationSolution, ConfigureWorkflowsAction $action): RedirectResponse
    {
        $action->handle($organizationSolution, $this->normalizedItems($request, 'blueprint_workflow_id'));

        return redirect()->route('organization_solutions.wizard.checklists.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình Workflows.');
    }

    // ── Bước 4b: Checklists ──────────────────────────────────────────────

    public function showChecklists(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.capabilities.workflows.phases.checklists', 'checklistConfigs');

        return view('organizationsolution::wizard.checklists', compact('organizationSolution'));
    }

    public function checklists(Request $request, OrganizationSolution $organizationSolution, ConfigureChecklistsAction $action): RedirectResponse
    {
        $action->handle($organizationSolution, $this->normalizedItems($request, 'blueprint_checklist_id'));

        return redirect()->route('organization_solutions.wizard.resources.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình Checklists.');
    }

    // ── Bước 5: Resources ────────────────────────────────────────────────

    public function showResources(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.resourceLinks', 'resourceOverrides');

        return view('organizationsolution::wizard.resources', compact('organizationSolution'));
    }

    public function resources(Request $request, OrganizationSolution $organizationSolution, ConfigureResourcesAction $action): RedirectResponse
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => filled($item['override_reference'] ?? null))
            ->values()->all();

        $action->handle($organizationSolution, $items);

        return redirect()->route('organization_solutions.wizard.ai.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình Resources.');
    }

    // ── Bước 6: AI ───────────────────────────────────────────────────────

    public function showAi(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.aiCapabilities', 'aiConfigs');

        return view('organizationsolution::wizard.ai', compact('organizationSolution'));
    }

    public function ai(Request $request, OrganizationSolution $organizationSolution, ConfigureAiAction $action): RedirectResponse
    {
        $action->handle($organizationSolution, $request->input('items', []));

        return redirect()->route('organization_solutions.wizard.roles.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình AI.');
    }

    // ── Bổ sung: Role Mapping (A07 §12) ──────────────────────────────────

    public function showRoles(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.deploymentRoles', 'roleMappings');

        return view('organizationsolution::wizard.roles', compact('organizationSolution'));
    }

    public function roles(Request $request, OrganizationSolution $organizationSolution, MapRolesAction $action): RedirectResponse
    {
        $action->handle($organizationSolution, $request->input('items', []));

        return redirect()->route('organization_solutions.wizard.dashboard.form', $organizationSolution)
            ->with('success', 'Đã lưu Role Mapping.');
    }

    // ── Bước 7: Dashboard ────────────────────────────────────────────────

    public function showDashboard(OrganizationSolution $organizationSolution): View
    {
        $organizationSolution->loadMissing('blueprintVersion.analytics', 'dashboardWidgets');

        return view('organizationsolution::wizard.dashboard', compact('organizationSolution'));
    }

    public function dashboard(Request $request, OrganizationSolution $organizationSolution, ConfigureDashboardAction $action): RedirectResponse
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => filled($item['title'] ?? null))
            ->values()->all();

        $action->handle($organizationSolution, $items);

        return redirect()->route('organization_solutions.wizard.review.form', $organizationSolution)
            ->with('success', 'Đã lưu cấu hình Dashboard.');
    }

    // ── Bước 8: Review ───────────────────────────────────────────────────

    public function showReview(OrganizationSolution $organizationSolution, ValidatePreDeployHandler $handler): View
    {
        $result = $handler->handle(new ValidatePreDeployQuery($organizationSolution->id));

        return view('organizationsolution::wizard.review', compact('organizationSolution', 'result'));
    }

    public function review(OrganizationSolution $organizationSolution, MarkSolutionReadyAction $action): RedirectResponse
    {
        try {
            $action->handle($organizationSolution);
        } catch (DomainException $e) {
            return back()->withErrors(['organization_solution' => $e->getMessage()]);
        }

        return redirect()->route('organization_solutions.index')
            ->with('success', "\"{$organizationSolution->name}\" đã sẵn sàng (ready) để deploy.");
    }

    /** Chuẩn hoá items[] từ form (bulk checkbox/select) — bỏ item không có key chính. */
    private function normalizedItems(Request $request, string $keyField): array
    {
        return collect($request->input('items', []))
            ->filter(fn ($item) => filled($item[$keyField] ?? null))
            ->values()->all();
    }
}
