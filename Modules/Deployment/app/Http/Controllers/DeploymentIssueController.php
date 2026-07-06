<?php

namespace Modules\Deployment\Http\Controllers;

use App\Foundation\Vertical\VerticalConfigService;
use App\Foundation\VerticalDefinition;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Deployment\Actions\AssignIssueOwnerAction;
use Modules\Deployment\Actions\ResolveDeploymentIssueAction;
use Modules\Deployment\Actions\StoreDeploymentIssueAction;
use Modules\Deployment\Actions\UpdateDeploymentIssueAction;
use Modules\Deployment\Data\AssignIssueOwnerData;
use Modules\Deployment\Data\StoreDeploymentIssueData;
use Modules\Deployment\Data\UpdateDeploymentIssueData;
use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Queries\ListDeploymentIssuesHandler;
use Modules\Deployment\Queries\ListDeploymentIssuesQuery;

class DeploymentIssueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DeploymentIssue::class);

        $vertical = $request->attributes->get('_vertical');

        $targetIds = DeploymentTarget::where('vertical_code', $vertical->code())->pluck('id');

        $query = new ListDeploymentIssuesQuery(
            target_id:  $request->integer('target_id') ?: null,
            project_id: $request->integer('project_id') ?: null,
            severity:   $request->input('severity'),
            status:     $request->input('status'),
            issue_type: $request->input('issue_type') ?: null,
        );

        $issues    = (new ListDeploymentIssuesHandler)->handle($query)
            ->whereIn('deployment_target_id', $targetIds)
            ->paginate(25)->withQueryString();

        $severities = IssueSeverity::cases();
        $statuses   = IssueStatus::cases();
        $issueTypes = VerticalConfigService::issueTypes($vertical);
        $targets    = DeploymentTarget::where('vertical_code', $vertical->code())
            ->with('targetOrganization')->get();

        return view('deployment::issues.index', compact('vertical', 'issues', 'severities', 'statuses', 'issueTypes', 'targets'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DeploymentIssue::class);

        $vertical = $request->attributes->get('_vertical');
        $targets  = DeploymentTarget::where('vertical_code', $vertical->code())
            ->with('targetOrganization')->get();
        $severities = IssueSeverity::cases();
        $issueTypes = VerticalConfigService::issueTypes($vertical);

        // Đến từ trang chi tiết 1 target (nút "+ Báo cáo issue") — chọn sẵn để khỏi tìm lại trong dropdown.
        $selectedTargetId = $request->integer('deployment_target_id') ?: null;
        $selectedTarget    = $selectedTargetId ? $targets->firstWhere('id', $selectedTargetId) : null;

        // Chưa chọn target cụ thể → gộp theo tất cả tổ chức đang được triển khai trong danh
        // sách targets hiển thị (không phải TenantContext của người xem — đó chính là bug đã
        // tìm thấy ở Employee: lấy nhầm tổ chức vận hành thay vì tổ chức đang triển khai).
        $owners = $this->candidateOwners($vertical, $selectedTarget, $targets);

        return view('deployment::issues.create', compact(
            'vertical', 'targets', 'severities', 'issueTypes', 'selectedTarget', 'owners'
        ));
    }

    public function store(Request $request, StoreDeploymentIssueAction $action): RedirectResponse
    {
        $this->authorize('create', DeploymentIssue::class);

        $vertical = $request->attributes->get('_vertical');
        $data     = StoreDeploymentIssueData::validateAndCreate($request->all());
        $issue    = $action->handle($data);

        return redirect()
            ->route('deployment.issues.index', ['vertical' => $vertical->code()])
            ->with('success', 'Đã tạo issue thành công.');
    }

    public function show(Request $request, DeploymentIssue $issue): View
    {
        $this->authorize('view', $issue);

        $vertical = $request->attributes->get('_vertical');
        $issue->load(['target.targetOrganization', 'owner', 'createdBy']);
        $issueTypes = VerticalConfigService::issueTypes($vertical);
        $owners     = $this->candidateOwners($vertical, $issue->target);

        return view('deployment::issues.show', compact('vertical', 'issue', 'issueTypes', 'owners'));
    }

    public function update(Request $request, DeploymentIssue $issue, UpdateDeploymentIssueAction $action): RedirectResponse
    {
        $this->authorize('update', $issue);

        $data = UpdateDeploymentIssueData::validateAndCreate($request->all());
        $action->handle($issue, $data);

        return back()->with('success', 'Đã cập nhật issue.');
    }

    public function resolve(Request $request, DeploymentIssue $issue, ResolveDeploymentIssueAction $action): RedirectResponse
    {
        $this->authorize('resolve', $issue);

        try {
            $action->handle($issue);
            return back()->with('success', 'Đã đánh dấu issue đã giải quyết.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['resolve' => $e->getMessage()]);
        }
    }

    public function assignOwner(Request $request, DeploymentIssue $issue, AssignIssueOwnerAction $action): RedirectResponse
    {
        $this->authorize('update', $issue);

        $data = AssignIssueOwnerData::validateAndCreate($request->all());
        $action->handle($issue, $data);

        return back()->with('success', 'Đã cập nhật người phụ trách issue.');
    }

    /**
     * Users có role thực địa của vertical (pm/surveyor/data_entry/data_ops), scoped theo
     * target_organization_id (tổ chức ĐANG ĐƯỢC TRIỂN KHAI) — không phải organization_id
     * (tenant vận hành) và không phải TenantContext của người xem. Cùng lỗi đã sửa ở
     * Employee (targets/checklist assignment) — người phụ trách issue thuộc về tổ chức
     * đang triển khai, không phải tổ chức của người đang xem trang.
     *
     * $target: đã chọn 1 target cụ thể (trang show, hoặc create đã preselect) → scope đúng
     * 1 tổ chức đó. $targets: chưa chọn (create chưa preselect) → gộp theo mọi tổ chức xuất
     * hiện trong danh sách target đang hiển thị.
     */
    private function candidateOwners(VerticalDefinition $vertical, ?DeploymentTarget $target = null, ?Collection $targets = null)
    {
        $roleNames = collect(['pm', 'surveyor', 'data_entry', 'data_ops'])
            ->map(fn ($suffix) => $vertical->code() . '_' . $suffix)
            ->all();

        $orgIds = $target
            ? [$target->target_organization_id]
            : ($targets?->pluck('target_organization_id')->filter()->unique()->all() ?? []);

        if (empty($orgIds)) {
            return collect();
        }

        return User::role($roleNames)
            ->whereIn('organization_id', $orgIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
