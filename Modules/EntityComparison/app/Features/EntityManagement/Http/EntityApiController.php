<?php

namespace Modules\EntityComparison\Features\EntityManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\EntityComparison\Features\EntityManagement\Queries\ListEntitiesForAdminHandler;
use Modules\EntityComparison\Features\EntityManagement\Queries\ListEntitiesForAdminQuery;
use Modules\EntityComparison\Models\Entity;

/**
 * JSON backend cho Tabulator ở dashboard/entity-comparison/entities — remote pagination/sort/
 * filter, trả {data, last_page, total}. Đúng pattern ArticleApiController (Modules\Post).
 */
class EntityApiController extends Controller
{
    public function index(Request $request, ListEntitiesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Entity::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'entity_type_id' => ['nullable', 'integer'],
        ]);

        // Tabulator (sortMode: 'remote') gửi mảng sort[{field,dir}] — cùng cách đọc ArticleApiController.
        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListEntitiesForAdminQuery(
            search: $validated['search'] ?? null,
            entityTypeId: $validated['entity_type_id'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);
        $user = $request->user();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Entity $entity) => $this->mapNode($entity, $user)),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    private function mapNode(Entity $entity, User $user): array
    {
        return [
            'id' => $entity->id,
            'uuid' => $entity->uuid,
            'name' => $entity->name,
            'cover_url' => $entity->getMediaUrl('cover', 'thumb'),
            'entity_type_name' => $entity->entityType?->name,
            'is_active' => (bool) $entity->is_active,
            'sort_order' => $entity->sort_order,
            'edit_url' => route('backend.entity_comparison.entities.edit', $entity),
            'destroy_url' => route('backend.entity_comparison.entities.destroy', $entity),
            'can_update' => $user->can('update', $entity),
            'can_delete' => $user->can('delete', $entity),
        ];
    }
}
