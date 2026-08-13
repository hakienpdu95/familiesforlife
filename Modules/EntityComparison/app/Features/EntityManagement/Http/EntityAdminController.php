<?php

namespace Modules\EntityComparison\Features\EntityManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Features\EntityManagement\Actions\CreateEntityAction;
use Modules\EntityComparison\Features\EntityManagement\Actions\DeleteEntityAction;
use Modules\EntityComparison\Features\EntityManagement\Actions\SetCriterionValuesAction;
use Modules\EntityComparison\Features\EntityManagement\Actions\UpdateEntityAction;
use Modules\EntityComparison\Features\EntityManagement\Data\EntityData;
use Modules\EntityComparison\Features\EntityManagement\Http\Requests\StoreEntityRequest;
use Modules\EntityComparison\Features\EntityManagement\Http\Requests\UpdateEntityRequest;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Models\EntityType;
use Modules\EntityComparison\Support\CriterionValueResolver;

/** spec/Entity_Comparison_Module_Technical_Spec.md §7.1 mục 4, §8 — CRUD Entity + nhập giá trị tiêu chí. */
class EntityAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Entity::class, 'entity');
    }

    /** Dữ liệu bảng lấy qua EntityApiController (Tabulator remote pagination/sort/filter, đúng pattern ArticleApiController). */
    public function index(): View
    {
        $entityTypes = EntityType::active()->orderBy('name')->get(['id', 'name']);

        return view('entitycomparison::admin.entities.index', compact('entityTypes'));
    }

    public function create(): View
    {
        $entityTypes = EntityType::active()->with('criteria.options')->orderBy('name')->get();

        return view('entitycomparison::admin.entities.create', compact('entityTypes'));
    }

    public function store(
        StoreEntityRequest $request,
        CreateEntityAction $createEntity,
        SetCriterionValuesAction $setCriterionValues,
    ): RedirectResponse {
        $validated = $request->validated();
        $data = EntityData::from($validated);
        $entity = $createEntity->handle($data, $request->file('cover'));
        $setCriterionValues->handle($entity, $this->normalizedCriterionValues($data->entity_type_id, $validated['criterion_values'] ?? []));

        return redirect()->route('backend.entity_comparison.entities.index')
            ->with('success', "Đã tạo đối tượng \"{$entity->name}\".");
    }

    public function edit(Entity $entity): View
    {
        $entity->load(['entityType.criteria.options', 'criterionValues.criterion', 'criterionValues.option', 'criterionValues.options']);
        $entityTypes = EntityType::active()->with('criteria.options')->orderBy('name')->get();

        $resolver = app(CriterionValueResolver::class);
        $currentValues = [];

        foreach ($entity->entityType->criteria as $criterion) {
            $value = $entity->criterionValues->firstWhere('criterion_id', $criterion->id);

            if (! $value) {
                continue;
            }

            $result = $resolver->read($value, $criterion);

            $currentValues[$criterion->id] = match (true) {
                $result->isMultiple() => $result->asOptions()->pluck('id')->all(),
                $criterion->type === CriterionType::Select => $result->asScalar()?->id,
                // format('Y-m-d') — khớp định dạng HTML <input type="date"> đang dùng ở _form.blade.php.
                $criterion->type === CriterionType::Date => $result->asScalar()?->format('Y-m-d'),
                default => $result->asScalar(),
            };
        }

        return view('entitycomparison::admin.entities.edit', compact('entity', 'entityTypes', 'currentValues'));
    }

    public function update(
        UpdateEntityRequest $request,
        Entity $entity,
        UpdateEntityAction $updateEntity,
        SetCriterionValuesAction $setCriterionValues,
    ): RedirectResponse {
        $validated = $request->validated();
        $data = EntityData::from($validated);
        $updateEntity->handle($entity, $data, $request->file('cover'));
        $setCriterionValues->handle($entity->fresh(), $this->normalizedCriterionValues($data->entity_type_id, $validated['criterion_values'] ?? []));

        return redirect()->route('backend.entity_comparison.entities.index')
            ->with('success', 'Cập nhật đối tượng thành công.');
    }

    public function destroy(Request $request, Entity $entity, DeleteEntityAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($entity);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá đối tượng.']);
        }

        return redirect()->route('backend.entity_comparison.entities.index')
            ->with('success', 'Đã xoá đối tượng.');
    }

    /**
     * HTML form KHÔNG submit checkbox chưa tick — nếu admin bỏ tick hết option của 1 tiêu chí
     * multi_select, key `criterion_values.{id}` biến mất hoàn toàn khỏi request thay vì gửi mảng
     * rỗng. `SetCriterionValuesAction` chỉ xoá giá trị cũ khi key CÓ MẶT nhưng rỗng — key vắng
     * mặt bị bỏ qua im lặng, để lại `criterion_value_options` cũ không bị xoá. Chuẩn hoá lại: duyệt
     * toàn bộ Criteria đã gán cho entity_type_id, key nào không có trong request thì set null —
     * đảm bảo mọi tiêu chí được xử lý tường minh (ghi hoặc xoá), không có key nào bị "quên".
     *
     * @param  array<int, mixed>  $submitted
     * @return array<int, mixed>
     */
    private function normalizedCriterionValues(int $entityTypeId, array $submitted): array
    {
        $criteria = EntityType::find($entityTypeId)?->criteria ?? collect();

        $normalized = [];
        foreach ($criteria as $criterion) {
            $normalized[$criterion->id] = $submitted[$criterion->id] ?? null;
        }

        return $normalized;
    }
}
