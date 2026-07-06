<?php

namespace Modules\Deployment\Http\Controllers;

use App\Foundation\Vertical\VerticalTemplate;
use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Actions\AdvancePhaseAction;
use Modules\Deployment\Actions\AssignTargetEmployeeAction;
use Modules\Deployment\Actions\CreateDeploymentTargetAction;
use Modules\Deployment\Data\AssignTargetEmployeeData;
use Modules\Deployment\Data\CreateDeploymentTargetData;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Queries\ListDeploymentTargetsHandler;
use Modules\Deployment\Queries\ListDeploymentTargetsQuery;
use Modules\Employee\Models\Employee;
use Modules\Project\Models\Project;

class DeploymentTargetController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DeploymentTarget::class);

        $vertical = $request->attributes->get('_vertical');

        $query = new ListDeploymentTargetsQuery(
            vertical_code: $vertical->code(),
            phase:         $request->input('phase'),
            search:        $request->input('search'),
            project_id:    $request->integer('project_id') ?: null,
        );

        $targets     = (new ListDeploymentTargetsHandler)->handle($query)->paginate(20)->withQueryString();
        $phases      = $vertical->phases();
        $phaseLabels = $vertical->phaseLabels();
        $projects    = Project::where('vertical_code', $vertical->code())->orderBy('name')->get(['id', 'name']);

        return view('deployment::targets.index', compact('vertical', 'targets', 'phases', 'phaseLabels', 'projects'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DeploymentTarget::class);

        $vertical  = $request->attributes->get('_vertical');
        $projects  = Project::where('vertical_code', $vertical->code())->orderBy('name')->get(['id', 'name']);
        $employees = Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_code']);

        [$organizations, $orgLocked] = $this->resolveOrganizations();

        return view('deployment::targets.create', compact('vertical', 'projects', 'employees', 'organizations', 'orgLocked'));
    }

    public function store(Request $request, CreateDeploymentTargetAction $action): RedirectResponse
    {
        $this->authorize('create', DeploymentTarget::class);

        $vertical = $request->attributes->get('_vertical');
        $data     = CreateDeploymentTargetData::validateAndCreate($request->all());
        $target   = $action->handle($data, $vertical);

        return redirect()
            ->route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id])
            ->with('success', "Đã thêm {$vertical->targetLabel()} thành công.");
    }

    public function show(Request $request, DeploymentTarget $target): View
    {
        $this->authorize('view', $target);

        $vertical = $request->attributes->get('_vertical');

        $target->load([
            'targetOrganization',
            'assignedEmployee',
            'project',
            'createdBy',
        ]);

        $checklist   = $target->checklistForPhase($target->current_phase)
            ->with(['doneBy', 'progressLogs.loggedBy', 'assignedEmployee'])
            ->get();
        $phaseProgress = $target->phaseProgress($target->current_phase);
        $phases      = $vertical->phases();
        $phaseLabels = $vertical->phaseLabels();
        $openIssues  = $target->issues()->where('status', 'open')->count();
        // withoutTenant() + where tường minh theo target_organization_id (tổ chức ĐANG ĐƯỢC
        // TRIỂN KHAI, vd. HTX) — không phải organization_id (tenant vận hành vertical, vd.
        // THUCHOCVN) và không phải TenantContext của người xem. Nhân viên "phụ trách"
        // checklist/target thuộc về chính tổ chức đang được triển khai.
        $employees   = Employee::withoutTenant()
            ->where('organization_id', $target->target_organization_id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        return view('deployment::targets.show', compact(
            'vertical', 'target', 'checklist', 'phaseProgress', 'phases', 'phaseLabels', 'openIssues', 'employees'
        ));
    }

    public function assignEmployee(Request $request, DeploymentTarget $target, AssignTargetEmployeeAction $action): RedirectResponse
    {
        $this->authorize('update', $target);

        $data = AssignTargetEmployeeData::validateAndCreate($request->all());
        $action->handle($target, $data);

        return back()->with('success', 'Đã cập nhật người phụ trách.');
    }

    public function lookup(Request $request): JsonResponse
    {
        $taxCode = trim((string) $request->input('tax_code', ''));

        if (strlen($taxCode) < 8) {
            return response()->json(['found' => false]);
        }

        $org = Organization::where('tax_code', $taxCode)->first();

        if (! $org) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'org'   => [
                'id'           => $org->id,
                'name'         => $org->name,
                'phone'        => $org->phone,
                'email'        => $org->email,
                'full_address' => $org->full_address,
            ],
        ]);
    }

    public function advance(Request $request, DeploymentTarget $target, AdvancePhaseAction $action): RedirectResponse
    {
        $this->authorize('advance', $target);

        $vertical = $request->attributes->get('_vertical');

        try {
            $action->handle($target, $vertical);
            return back()->with('success', 'Đã chuyển sang phase tiếp theo.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['advance' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: trả về readiness_template_slug / data_collection_template_slug đã cấu hình
     * cho vertical hiện tại của 1 organization — dùng để preview khi chọn "Tổ chức tham chiếu"
     * trên form tạo target.
     */
    public function organizationSlugs(Request $request): JsonResponse
    {
        $vertical = $request->attributes->get('_vertical');
        $orgId    = $request->integer('organization_id');

        if (! $orgId) {
            return response()->json(['found' => false]);
        }

        $template = VerticalTemplate::where('organization_id', $orgId)
            ->where('code', $vertical->code())
            ->first(['readiness_template_slug', 'data_collection_template_slug']);

        if (! $template) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'                         => true,
            'readiness_template_slug'       => $template->readiness_template_slug,
            'data_collection_template_slug' => $template->data_collection_template_slug,
        ]);
    }

    /** Giống DepartmentController::_resolveOrganizations() — khoá theo org của user, hoặc cho chọn nếu là user hệ thống. */
    private function resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), true];
        }

        return [Organization::orderBy('name')->get(['id', 'name']), false];
    }
}
