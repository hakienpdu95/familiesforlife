<?php

namespace Modules\Event\Features\EventCategoryManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Features\EventCategoryManagement\Queries\GetEventCategoryTreeHandler;
use Modules\Event\Features\EventCategoryManagement\Queries\GetEventCategoryTreeQuery;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminHandler;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminQuery;
use Modules\Event\Models\EventCategory;

/**
 * JSON backend cho Tabulator (dataTree) ở dashboard/events/categories — cùng pattern
 * Modules/Post/.../CategoryApiController: không tìm kiếm → cây đầy đủ cha/con (children lồng
 * nhau); có tìm kiếm → danh sách phẳng. Không phân trang remote — cây tải toàn bộ 1 lượt.
 */
class EventCategoryApiController extends Controller
{
    public function index(
        Request $request,
        ListEventCategoriesForAdminHandler $flatHandler,
        GetEventCategoryTreeHandler $treeHandler,
    ): JsonResponse {
        $this->authorize('viewAny', EventCategory::class);

        $search = $request->string('search')->value() ?: null;
        $user   = $request->user();

        if ($search) {
            $flat = $flatHandler->handle(new ListEventCategoriesForAdminQuery(search: $search));
            $data = $flat->map(fn (EventCategory $c) => $this->mapFlat($c, $user))->values();
        } else {
            $tree = $treeHandler->handle(new GetEventCategoryTreeQuery());
            $data = $tree->map(fn (EventCategory $c) => $this->mapNode($c, $user))->values();
        }

        return response()->json(['data' => $data]);
    }

    /** @return array<string, mixed> */
    private function mapNode(EventCategory $node, User $user): array
    {
        $children = $node->children
            ->map(fn (EventCategory $child) => $this->mapNode($child, $user))
            ->values()
            ->all();

        return [
            ...$this->baseFields($node, $user),
            'children' => $children,
        ];
    }

    /** @return array<string, mixed> */
    private function mapFlat(EventCategory $category, User $user): array
    {
        return [
            ...$this->baseFields($category, $user),
            'parent_name' => $category->parent?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function baseFields(EventCategory $category, User $user): array
    {
        return [
            'id'          => $category->id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'color_hex'   => $category->color_hex,
            'events_count' => $category->events_count,
            'is_active'   => (bool) $category->is_active,
            'edit_url'    => route('backend.event.categories.edit', $category),
            'destroy_url' => route('backend.event.categories.destroy', $category),
            'can_update'  => $user->can('update', $category),
            'can_delete'  => $user->can('delete', $category),
        ];
    }
}
