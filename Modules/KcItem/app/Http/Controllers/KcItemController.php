<?php

namespace Modules\KcItem\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\KcCategory\Models\KcCategory;
use Modules\KcItem\Actions\Backend\ApproveKcItemAction;
use Modules\KcItem\Actions\Backend\RollbackKcItemAction;
use Modules\KcItem\Models\KcTag;
use Modules\KcItem\Actions\Backend\ArchiveKcItemAction;
use Modules\KcItem\Actions\Backend\DestroyKcItemAction;
use Modules\KcItem\Actions\Backend\RejectKcItemAction;
use Modules\KcItem\Actions\Backend\StoreKcItemAction;
use Modules\KcItem\Actions\Backend\SubmitKcItemAction;
use Modules\KcItem\Actions\Backend\UpdateKcItemAction;
use Modules\KcItem\Data\Requests\StoreKcItemData;
use Modules\KcItem\Data\Requests\UpdateKcItemData;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Enums\KcItemType;
use Modules\KcItem\Enums\KcItemVisibility;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcVersionHistory;
use Modules\KcItem\Actions\Backend\LogKcViewAction;
use Modules\KcItem\Services\KcItemAccessService;

class KcItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(KcItem::class, 'kc_item');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = KcItem::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = "draft"          THEN 1 ELSE 0 END) as total_draft,
                 SUM(CASE WHEN status = "pending_review" THEN 1 ELSE 0 END) as total_pending,
                 SUM(CASE WHEN status = "approved"       THEN 1 ELSE 0 END) as total_approved'
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalDraft    = (int) ($counts->total_draft    ?? 0);
        $totalPending  = (int) ($counts->total_pending  ?? 0);
        $totalApproved = (int) ($counts->total_approved ?? 0);

        $statuses    = KcItemStatus::options();
        $types       = KcItemType::options();
        $visibilities = KcItemVisibility::options();

        $categories = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        $tagsApiUrl = route('backend.api.kc-tags.select');

        return view('kcitem::index', compact(
            'totalAll', 'totalDraft', 'totalPending', 'totalApproved',
            'statuses', 'types', 'visibilities', 'categories', 'tagsApiUrl'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $categories = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        $types       = KcItemType::options();
        $visibilities = KcItemVisibility::options();

        $tagsApiUrl = route('backend.api.kc-tags.select');

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('kcitem::create', compact('categories', 'types', 'visibilities', 'tagsApiUrl', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreKcItemAction $action): RedirectResponse
    {
        $data   = StoreKcItemData::validateAndCreate($request->all());
        $kcItem = $action->handle($data);

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Tài liệu "' . $kcItem->title . '" đã được tạo thành công.');
    }

    public function show(KcItem $kcItem, KcItemAccessService $accessService, Request $request)
    {
        $user = auth()->user();

        // Kiểm tra quyền xem theo visibility (6 bước logic)
        if (! $accessService->canView($user, $kcItem)) {
            abort(403, 'Bạn không có quyền xem tài liệu này.');
        }

        $kcItem->load([
            'category:id,name,color_hex',
            'owner:id,name',
            'approvedBy:id,name',
            'tags:id,name,slug,color_hex',
            'attachments',
            'versionHistories' => fn ($q) => $q->with('changedBy:id,name')->orderByDesc('version_number'),
        ]);

        $canSubmit   = $user->can('submit', $kcItem);
        $canApprove  = $user->can('approve', $kcItem);
        $canReject   = $user->can('reject', $kcItem);
        $canArchive  = $user->can('archive', $kcItem);
        $canRollback = $user->can('rollback', $kcItem);
        $canManageAccess = $user->can('update', $kcItem);

        // Thống kê feedback
        $feedbackSummary = [
            'total'           => $kcItem->feedbacks()->count(),
            'avg_rating'      => $kcItem->feedbacks()->whereNotNull('rating')->avg('rating'),
            'helpful_percent' => null,
        ];
        $helpfulTotal = $kcItem->feedbacks()->whereNotNull('is_helpful')->count();
        if ($helpfulTotal > 0) {
            $feedbackSummary['helpful_percent'] = round(
                ($kcItem->feedbacks()->where('is_helpful', true)->count() / $helpfulTotal) * 100
            );
        }

        $myFeedback = $kcItem->feedbacks()->where('user_id', $user->id)->first();

        // Track view (synchronous for MVP, UpdateKcViewCountJob is async)
        (new LogKcViewAction)->handle($kcItem, $request);

        return view('kcitem::show', compact(
            'kcItem', 'canSubmit', 'canApprove', 'canReject', 'canArchive',
            'canRollback', 'canManageAccess', 'feedbackSummary', 'myFeedback'
        ));
    }

    public function edit(KcItem $kcItem)
    {
        $orgId = TenantContext::getOrganizationId();

        $categories = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        $types       = KcItemType::options();
        $visibilities = KcItemVisibility::options();

        $tagsApiUrl   = route('backend.api.kc-tags.select');
        $selectedTags = $kcItem->tags()->pluck('kc_tags.id')->toArray();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('kcitem::edit', compact('kcItem', 'categories', 'types', 'visibilities', 'tagsApiUrl', 'selectedTags', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, KcItem $kcItem, UpdateKcItemAction $action): RedirectResponse
    {
        $data = UpdateKcItemData::validateAndCreate($request->all());
        $action->handle($kcItem, $data);

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Cập nhật tài liệu thành công.');
    }

    public function destroy(Request $request, KcItem $kcItem, DestroyKcItemAction $action): RedirectResponse|JsonResponse
    {
        $title = $action->handle($kcItem);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tài liệu "' . $title . '".']);
        }

        return redirect()->route('backend.kc-items.index')
            ->with('success', 'Đã xóa tài liệu "' . $title . '".');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    // ── Version management ────────────────────────────────────────────────────

    public function showVersion(KcItem $kcItem, int $versionNumber, KcItemAccessService $accessService)
    {
        if (! $accessService->canView(auth()->user(), $kcItem)) {
            abort(403, 'Bạn không có quyền xem tài liệu này.');
        }

        $version = KcVersionHistory::where('item_id', $kcItem->id)
            ->where('version_number', $versionNumber)
            ->with('changedBy:id,name')
            ->firstOrFail();

        $canRollback = auth()->user()->can('rollback', $kcItem);

        return view('kcitem::version-show', compact('kcItem', 'version', 'canRollback'));
    }

    public function rollback(Request $request, KcItem $kcItem, int $versionNumber, RollbackKcItemAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('rollback', $kcItem);

        $action->handle($kcItem, $versionNumber);

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã rollback về version {$versionNumber}.", 'status' => KcItemStatus::Draft->value]);
        }

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', "Đã rollback về version {$versionNumber}. Tài liệu cần được duyệt lại.");
    }

    // ── Status transitions ────────────────────────────────────────────────────

    public function submit(Request $request, KcItem $kcItem, SubmitKcItemAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('submit', $kcItem);

        $action->handle($kcItem);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã gửi duyệt tài liệu.', 'status' => KcItemStatus::PendingReview->value]);
        }

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Đã gửi tài liệu để duyệt.');
    }

    public function approve(Request $request, KcItem $kcItem, ApproveKcItemAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $kcItem);

        $action->handle($kcItem, $request->input('change_summary'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tài liệu đã được duyệt.', 'status' => KcItemStatus::Approved->value]);
        }

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Tài liệu đã được duyệt thành công.');
    }

    public function reject(Request $request, KcItem $kcItem, RejectKcItemAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('reject', $kcItem);

        $request->validate(['reason' => 'required|string|max:500']);
        $action->handle($kcItem, $request->input('reason'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tài liệu đã bị từ chối.', 'status' => KcItemStatus::Rejected->value]);
        }

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Đã từ chối tài liệu.');
    }

    public function archive(Request $request, KcItem $kcItem, ArchiveKcItemAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('archive', $kcItem);

        $action->handle($kcItem);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tài liệu đã được lưu trữ.', 'status' => KcItemStatus::Archived->value]);
        }

        return redirect()->route('backend.kc-items.show', $kcItem)
            ->with('success', 'Đã lưu trữ tài liệu.');
    }
}
