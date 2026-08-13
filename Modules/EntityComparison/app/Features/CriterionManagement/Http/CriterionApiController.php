<?php

namespace Modules\EntityComparison\Features\CriterionManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\EntityType;

/**
 * JSON backend cho Tabulator dataTree ở dashboard/entity-comparison/criteria — nhóm theo
 * EntityType (đối tượng có nhiều tiêu chí), đúng pattern CategoryApiController của Modules\Post
 * (`Modules/Post/app/Features/CategoryManagement/Http/CategoryApiController.php`). Khác Post ở
 * chỗ đây chỉ 2 tầng cố định (EntityType → Criterion của chính nó, 1 Criterion chỉ thuộc đúng 1
 * EntityType — xem CreateCriterionAction/UpdateCriterionAction), không cần walker đệ quy nhiều
 * cấp như cây category tự tham chiếu.
 *
 * `row_id` dùng làm khoá nhận diện hàng cho Tabulator (`index: 'row_id'` ở JS) thay vì `id` thô —
 * EntityType và Criterion là 2 bảng khác nhau, `id` tự tăng của chúng CHẮC CHẮN trùng nhau (VD
 * entity_types.id=1 và criteria.id=1 cùng tồn tại), nếu dùng `id` làm khoá Tabulator sẽ nhầm hàng
 * khi xử lý selection/toggle.
 */
class CriterionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Criterion::class);

        $user = $request->user();
        $search = $request->string('search')->value() ?: null;

        $entityTypes = EntityType::query()
            ->with(['criteria' => function ($q) use ($search) {
                $q->when($search, fn ($qq) => $qq->where('criteria.name', 'like', "%{$search}%"))
                    ->withCount('options')
                    ->orderByPivot('sort_order');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $data = $entityTypes
            ->when($search, fn ($collection) => $collection->filter(fn (EntityType $et) => $et->criteria->isNotEmpty()))
            ->map(fn (EntityType $entityType) => $this->mapEntityTypeNode($entityType, $user))
            ->values();

        return response()->json(['data' => $data]);
    }

    private function mapEntityTypeNode(EntityType $entityType, User $user): array
    {
        return [
            'row_id' => 'type-'.$entityType->id,
            'row_type' => 'entity_type',
            'name' => $entityType->name,
            'is_active' => (bool) $entityType->is_active,
            'criteria_count' => $entityType->criteria->count(),
            'criteria_url' => route('backend.entity_comparison.entity_types.criteria.edit', $entityType),
            'children' => $entityType->criteria
                ->map(fn (Criterion $criterion) => $this->mapCriterionNode($criterion, $user))
                ->values()
                ->all(),
        ];
    }

    private function mapCriterionNode(Criterion $criterion, User $user): array
    {
        return [
            'row_id' => 'crit-'.$criterion->id,
            'row_type' => 'criterion',
            'name' => $criterion->name,
            'type_label' => $criterion->type->label(),
            'unit' => $criterion->unit,
            'options_count' => $criterion->options_count,
            'is_filterable' => (bool) $criterion->is_filterable,
            'is_comparable' => (bool) $criterion->is_comparable,
            // pivot của chính EntityType đang duyệt — §7.1 mục 3, is_required riêng theo type
            // (dù giờ mỗi Criterion chỉ thuộc 1 type, field vẫn đọc qua pivot vì đó là nơi lưu).
            'is_required' => (bool) $criterion->pivot->is_required,
            'edit_url' => route('backend.entity_comparison.criteria.edit', $criterion),
            'destroy_url' => route('backend.entity_comparison.criteria.destroy', $criterion),
            'can_update' => $user->can('update', $criterion),
            'can_delete' => $user->can('delete', $criterion),
        ];
    }
}
