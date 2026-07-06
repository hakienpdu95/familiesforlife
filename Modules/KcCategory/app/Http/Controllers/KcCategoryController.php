<?php

namespace Modules\KcCategory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\KcCategory\Actions\Backend\DestroyKcCategoryAction;
use Modules\KcCategory\Actions\Backend\StoreKcCategoryAction;
use Modules\KcCategory\Actions\Backend\UpdateKcCategoryAction;
use Modules\KcCategory\Data\Requests\StoreKcCategoryData;
use Modules\KcCategory\Data\Requests\UpdateKcCategoryData;
use Modules\KcCategory\Models\KcCategory;

class KcCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(KcCategory::class, 'kc_category');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactive,
                 SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as total_root'
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalInactive = (int) ($counts->total_inactive ?? 0);
        $totalRoot     = (int) ($counts->total_root     ?? 0);

        $statuses = [
            ['value' => '1', 'text' => 'Đang hiển thị'],
            ['value' => '0', 'text' => 'Ẩn'],
        ];

        return view('kccategory::index', compact(
            'totalAll', 'totalActive', 'totalInactive', 'totalRoot', 'statuses'
        ));
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcCategory::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $rows = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows->map(fn ($c) => [
            'id'   => $c->id,
            'text' => $c->name,
        ]));
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $parentOptions = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('kccategory::create', compact('parentOptions', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreKcCategoryAction $action): RedirectResponse
    {
        $data     = StoreKcCategoryData::validateAndCreate($request->all());
        $category = $action->handle($data);

        return redirect()->route('backend.kc-categories.show', $category)
            ->with('success', 'Danh mục "' . $category->name . '" đã được tạo thành công.');
    }

    public function show(KcCategory $kcCategory)
    {
        $kcCategory->load(['parent:id,name', 'children' => fn ($q) => $q->withCount('children')]);

        return view('kccategory::show', compact('kcCategory'));
    }

    public function edit(KcCategory $kcCategory)
    {
        $orgId = TenantContext::getOrganizationId();

        $parentOptions = KcCategory::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereNull('parent_id')
            ->active()
            ->where('id', '<>', $kcCategory->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['value' => $c->id, 'text' => $c->name])
            ->all();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('kccategory::edit', compact('kcCategory', 'parentOptions', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, KcCategory $kcCategory, UpdateKcCategoryAction $action): RedirectResponse
    {
        $data = UpdateKcCategoryData::validateAndCreate($request->all());
        $action->handle($kcCategory, $data);

        return redirect()->route('backend.kc-categories.show', $kcCategory)
            ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function destroy(Request $request, KcCategory $kcCategory, DestroyKcCategoryAction $action): RedirectResponse|JsonResponse
    {
        try {
            $name = $action->handle($kcCategory);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa danh mục "' . $name . '".']);
        }

        return redirect()->route('backend.kc-categories.index')
            ->with('success', 'Đã xóa danh mục "' . $name . '".');
    }
}
