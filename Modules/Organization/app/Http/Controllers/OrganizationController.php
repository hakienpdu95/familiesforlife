<?php

namespace Modules\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Enums\OrganizationStatus;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Province;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\ArchiveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\RejectAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\Organization\Actions\Backend\DestroyOrganizationAction;
use Modules\Organization\Actions\Backend\StoreOrganizationAction;
use Modules\Organization\Actions\Backend\UpdateOrganizationAction;
use Modules\Organization\Data\Requests\StoreOrganizationData;
use Modules\Organization\Data\Requests\UpdateOrganizationData;
use Modules\Organization\Models\Organization;
use Modules\Organization\Queries\GetOrganizationHandler;
use Modules\Organization\Queries\GetOrganizationQuery;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index()
    {
        // Single query merges all stat counts
        $counts = Organization::withoutTenant()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_suspended',
                [OrganizationStatus::Active->value, OrganizationStatus::Suspended->value]
            )
            ->first();

        $totalAll       = (int) ($counts->total_all       ?? 0);
        $totalActive    = (int) ($counts->total_active    ?? 0);
        $totalSuspended = (int) ($counts->total_suspended ?? 0);

        $provinces = Province::where('is_active', true)
            ->orderBy('name')
            ->get(['province_code', 'name']);

        $statuses = collect(OrganizationStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('organization::index', compact(
            'totalAll', 'totalActive', 'totalSuspended',
            'provinces', 'statuses'
        ));
    }

    public function create()
    {
        return view('organization::create');
    }

    public function store(Request $request, StoreOrganizationAction $action): RedirectResponse
    {
        $data = StoreOrganizationData::validateAndCreate($request->all());

        $organization = $action->handle($data);

        return redirect()->route('backend.organizations.show', $organization)
            ->with('success', 'Tổ chức "' . $organization->name . '" đã được tạo thành công.');
    }

    public function show(Organization $organization, GetOrganizationHandler $handler)
    {
        $organization = $handler->handle(new GetOrganizationQuery($organization));
        $members = $organization->latestMembers;

        return view('organization::show', compact('organization', 'members'));
    }

    public function edit(Organization $organization)
    {
        $organization->loadCount('members');

        return view('organization::edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization, UpdateOrganizationAction $action): RedirectResponse
    {
        $data = UpdateOrganizationData::validateAndCreate($request->all());

        $action->handle($organization, $data);

        return redirect()->route('backend.organizations.show', $organization)
            ->with('success', 'Cập nhật tổ chức thành công.');
    }

    public function destroy(Request $request, Organization $organization, DestroyOrganizationAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($organization);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tổ chức "' . $name . '".' ]);
        }

        return redirect()->route('backend.organizations.index')
            ->with('success', 'Đã xóa tổ chức "' . $name . '".');
    }

    // ── Approval workflow — Platform Approval Gateway (Hà Kiên nội bộ) ─────────────────
    // approve/reject/publishApproval/archiveApproval do content_moderator xử lý (tài khoản
    // organization_id=null) — bọc trong TenantContext::runForOrganization() để các query nội
    // bộ (ApprovalSubject…) resolve đúng tổ chức đang xử lý, không phải tổ chức của người thao
    // tác (xem chú thích tương tự ở Modules/Product/.../ProductAdminController).

    public function submitApproval(Organization $organization, SubmitForApprovalAction $action): RedirectResponse
    {
        $this->authorize('submitForApproval', $organization);

        return $this->runApprovalTransition($organization, fn () => $action->handle($organization), 'Đã gửi hồ sơ tổ chức để chờ duyệt.');
    }

    public function approveContent(Organization $organization, ApproveAction $action): RedirectResponse
    {
        $this->authorize('approve', $organization);

        return $this->runApprovalTransition($organization, fn () => $action->handle($organization), 'Đã duyệt hồ sơ tổ chức.');
    }

    public function rejectContent(Request $request, Organization $organization, RejectAction $action): RedirectResponse
    {
        $this->authorize('reject', $organization);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runApprovalTransition($organization, fn () => $action->handle($organization, $reason), 'Đã từ chối duyệt hồ sơ tổ chức.');
    }

    public function publishContent(Organization $organization, PublishAction $action): RedirectResponse
    {
        $this->authorize('publishApproval', $organization);

        return $this->runApprovalTransition($organization, fn () => $action->handle($organization), 'Đã duyệt xuất bản hồ sơ tổ chức.');
    }

    public function archiveContent(Organization $organization, ArchiveAction $action): RedirectResponse
    {
        $this->authorize('archiveApproval', $organization);

        return $this->runApprovalTransition($organization, fn () => $action->handle($organization), 'Đã lưu trữ hồ sơ tổ chức.');
    }

    private function runApprovalTransition(Organization $organization, \Closure $callback, string $successMessage): RedirectResponse
    {
        try {
            TenantContext::runForOrganization($organization, $callback);
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }
}
