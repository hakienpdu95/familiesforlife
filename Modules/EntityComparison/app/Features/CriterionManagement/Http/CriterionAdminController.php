<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Features\CriterionManagement\Actions\CreateCriterionAction;
use Modules\EntityComparison\Features\CriterionManagement\Actions\DeleteCriterionAction;
use Modules\EntityComparison\Features\CriterionManagement\Actions\UpdateCriterionAction;
use Modules\EntityComparison\Features\CriterionManagement\Data\CriterionData;
use Modules\EntityComparison\Features\CriterionManagement\Http\Requests\StoreCriterionRequest;
use Modules\EntityComparison\Features\CriterionManagement\Http\Requests\UpdateCriterionRequest;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\EntityType;

/** spec/Entity_Comparison_Module_Technical_Spec.md §7.1 mục 2, §8 — CRUD Criterion + options con. */
class CriterionAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Criterion::class, 'criterion');
    }

    /** Dữ liệu bảng lấy qua CriterionApiController (Tabulator dataTree — nhóm theo EntityType, đúng pattern CategoryApiController của Modules\Post). */
    public function index(): View
    {
        return view('entitycomparison::admin.criteria.index');
    }

    public function create(): View
    {
        $types = CriterionType::cases();
        // is_active phải có trong select — _form.blade.php (dùng chung create/edit) đọc
        // $entityType->is_active để hiện badge "đã tắt"; thiếu cột này Model::shouldBeStrict()
        // (non-production) ném MissingAttributeException ngay khi Blade truy cập.
        $entityTypes = EntityType::active()->orderBy('name')->get(['id', 'name', 'is_active']);

        return view('entitycomparison::admin.criteria.create', compact('types', 'entityTypes'));
    }

    public function store(StoreCriterionRequest $request, CreateCriterionAction $action): RedirectResponse
    {
        $data = CriterionData::from($request->validated());
        $criterion = $action->handle($data);

        return redirect()->route('backend.entity_comparison.criteria.index')
            ->with('success', "Đã tạo tiêu chí \"{$criterion->name}\".");
    }

    public function edit(Criterion $criterion): View
    {
        $types = CriterionType::cases();
        $criterion->load('options', 'entityTypes');
        $currentEntityTypeId = $criterion->entityTypes->first()?->id;

        // is_active=true HOẶC đang là entity type hiện tại — type bị tắt sau khi đã gán vẫn phải
        // còn trong <select> để không bị mất lựa chọn hiện tại khi render form.
        $entityTypes = EntityType::query()
            ->where('is_active', true)
            ->when($currentEntityTypeId, fn ($q) => $q->orWhere('id', $currentEntityTypeId))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return view('entitycomparison::admin.criteria.edit', compact('criterion', 'types', 'entityTypes', 'currentEntityTypeId'));
    }

    public function update(UpdateCriterionRequest $request, Criterion $criterion, UpdateCriterionAction $action): RedirectResponse
    {
        $data = CriterionData::from($request->validated());
        $action->handle($criterion, $data);

        return redirect()->route('backend.entity_comparison.criteria.index')
            ->with('success', 'Cập nhật tiêu chí thành công.');
    }

    public function destroy(Request $request, Criterion $criterion, DeleteCriterionAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($criterion);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá tiêu chí.']);
        }

        return redirect()->route('backend.entity_comparison.criteria.index')
            ->with('success', 'Đã xoá tiêu chí.');
    }
}
