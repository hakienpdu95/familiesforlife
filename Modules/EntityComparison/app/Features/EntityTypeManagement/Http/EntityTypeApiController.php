<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\EntityComparison\Features\EntityTypeManagement\Queries\ListEntityTypesForAdminHandler;
use Modules\EntityComparison\Features\EntityTypeManagement\Queries\ListEntityTypesForAdminQuery;
use Modules\EntityComparison\Models\EntityType;

/**
 * JSON backend cho Tabulator ở dashboard/entity-comparison/entity-types — remote pagination/sort/
 * filter, trả {data, last_page, total}. Đúng pattern ArticleApiController (Modules\Post) —
 * EntityType là danh sách phẳng (khác criteria — nhóm theo cây, xem CriterionApiController).
 */
class EntityTypeApiController extends Controller
{
    public function index(Request $request, ListEntityTypesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', EntityType::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        // Tabulator (sortMode: 'remote') gửi mảng sort[{field,dir}] — cùng cách đọc ArticleApiController.
        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListEntityTypesForAdminQuery(
            search: $validated['search'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);
        $user = $request->user();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (EntityType $entityType) => $this->mapNode($entityType, $user)),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    private function mapNode(EntityType $entityType, User $user): array
    {
        return [
            'id' => $entityType->id,
            'name' => $entityType->name,
            'slug' => $entityType->slug,
            'cover_url' => $entityType->getMediaUrl('cover', 'thumb'),
            'is_active' => (bool) $entityType->is_active,
            'sort_order' => $entityType->sort_order,
            'entities_count' => $entityType->entities_count,
            'criteria_count' => $entityType->criteria_count,
            'edit_url' => route('backend.entity_comparison.entity_types.edit', $entityType),
            'destroy_url' => route('backend.entity_comparison.entity_types.destroy', $entityType),
            'criteria_url' => route('backend.entity_comparison.entity_types.criteria.edit', $entityType),
            'can_update' => $user->can('update', $entityType),
            'can_delete' => $user->can('delete', $entityType),
        ];
    }
}
