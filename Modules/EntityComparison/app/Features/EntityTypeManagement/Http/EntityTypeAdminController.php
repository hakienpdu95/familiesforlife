<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\EntityComparison\Features\CriterionManagement\Actions\AssignCriterionToEntityTypeAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Actions\CreateEntityTypeAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Actions\DeleteEntityTypeAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Actions\UpdateEntityTypeAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Data\EntityTypeData;
use Modules\EntityComparison\Features\EntityTypeManagement\Http\Requests\StoreEntityTypeRequest;
use Modules\EntityComparison\Features\EntityTypeManagement\Http\Requests\UpdateEntityTypeRequest;
use Modules\EntityComparison\Models\EntityType;

/** spec/Entity_Comparison_Module_Technical_Spec.md §7.1 mục 1/3, §8 — CRUD EntityType + gán Criteria. */
class EntityTypeAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EntityType::class, 'entity_type');
    }

    /** Dữ liệu bảng lấy qua EntityTypeApiController (Tabulator remote pagination/sort/filter, đúng pattern ArticleApiController). */
    public function index(): View
    {
        return view('entitycomparison::admin.entity-types.index');
    }

    public function create(): View
    {
        return view('entitycomparison::admin.entity-types.create');
    }

    public function store(StoreEntityTypeRequest $request, CreateEntityTypeAction $action): RedirectResponse
    {
        $data = EntityTypeData::from($request->validated());
        $entityType = $action->handle($data, $request->file('cover'));

        return redirect()->route('backend.entity_comparison.entity_types.index')
            ->with('success', "Đã tạo loại đối tượng \"{$entityType->name}\".");
    }

    public function edit(EntityType $entity_type): View
    {
        return view('entitycomparison::admin.entity-types.edit', ['entityType' => $entity_type]);
    }

    public function update(UpdateEntityTypeRequest $request, EntityType $entity_type, UpdateEntityTypeAction $action): RedirectResponse
    {
        $data = EntityTypeData::from($request->validated());
        $action->handle($entity_type, $data, $request->file('cover'));

        return redirect()->route('backend.entity_comparison.entity_types.index')
            ->with('success', 'Cập nhật loại đối tượng thành công.');
    }

    public function destroy(Request $request, EntityType $entity_type, DeleteEntityTypeAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($entity_type);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá loại đối tượng.']);
        }

        return redirect()->route('backend.entity_comparison.entity_types.index')
            ->with('success', 'Đã xoá loại đối tượng.');
    }

    /**
     * §7.1 mục 3 — CHỈ hiện Criteria ĐÃ thuộc EntityType này (gán qua entity_type_id bắt buộc,
     * 1-1, ở form tạo/sửa Criterion) — không còn là màn hình "chọn từ mọi tiêu chí" nữa, vì 1
     * Criterion chỉ thuộc đúng 1 EntityType, quyết định ở form Criterion, không phải ở đây.
     */
    public function editCriteria(EntityType $entity_type): View
    {
        $this->authorize('update', $entity_type);

        $criteria = $entity_type->criteria()->orderBy('sort_order')->orderBy('name')->get();

        return view('entitycomparison::admin.entity-types.criteria', [
            'entityType' => $entity_type,
            'criteria' => $criteria,
        ]);
    }

    /** Chỉ sửa is_required/sort_order của Criteria đã thuộc type này — không add/remove membership. */
    public function updateCriteria(Request $request, EntityType $entity_type, AssignCriterionToEntityTypeAction $action): RedirectResponse
    {
        $this->authorize('update', $entity_type);

        $validated = $request->validate([
            'is_required' => ['array'],
            'is_required.*' => ['boolean'],
            'sort_order' => ['array'],
            'sort_order.*' => ['integer', 'min:0'],
        ]);

        $action->handle($entity_type, $validated['is_required'] ?? [], $validated['sort_order'] ?? []);

        return redirect()->route('backend.entity_comparison.entity_types.criteria.edit', $entity_type)
            ->with('success', 'Đã cập nhật tiêu chí cho loại đối tượng.');
    }
}
