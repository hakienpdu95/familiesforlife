<?php

namespace Modules\Menu\Features\MenuManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Menu\Features\MenuManagement\Queries\GetMenuTreeForAdminHandler;
use Modules\Menu\Features\MenuManagement\Queries\GetMenuTreeForAdminQuery;
use Modules\Menu\Models\MenuItem;

/**
 * JSON backend cho Tabulator (dataTree) ở dashboard/menu/items — cùng dataTree Post/Product
 * Category, nhưng KHÔNG tách 2 nhánh tree/flat khi search như 2 module đó: dùng thẳng
 * GetMenuTreeForAdminHandler gốc (giữ nguyên hành vi cũ — filter label rồi dựng cây từ tập đã
 * lọc; mục con khớp mà cha không khớp sẽ không xuất hiện, không đổi gì ở đây). Không phân trang
 * — cây menu luôn tải toàn bộ 1 lượt (tối đa 3 cấp, danh sách nhỏ).
 */
class MenuItemApiController extends Controller
{
    public function index(Request $request, GetMenuTreeForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', MenuItem::class);

        $tree = $handler->handle(new GetMenuTreeForAdminQuery(
            location: $request->string('location')->value() ?: null,
            search: $request->string('search')->trim()->value() ?: null,
        ));

        $user = $request->user();
        $data = $this->mapLevel($tree, $user);

        return response()->json(['data' => $data]);
    }

    /**
     * Map 1 cấp (cùng parent_id) — tính prev_id/next_id NGAY TẠI ĐÂY (cùng $siblings->search()
     * đã dùng ở _row.blade.php cũ) để nút Lên/Xuống biết hoán đổi sort_order với ai, không phải
     * suy ra lại ở phía client qua Tabulator tree API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapLevel(Collection $siblings, User $user): array
    {
        return $siblings->values()->map(function (MenuItem $item, int $index) use ($siblings, $user): array {
            $prev = $index > 0 ? $siblings[$index - 1] : null;
            $next = $index < $siblings->count() - 1 ? $siblings[$index + 1] : null;

            return $this->mapNode($item, $user, $prev, $next);
        })->all();
    }

    /** @return array<string, mixed> */
    private function mapNode(MenuItem $item, User $user, ?MenuItem $prev, ?MenuItem $next): array
    {
        return [
            'id'              => $item->id,
            'label'           => $item->label,
            'icon'            => $item->icon,
            'location'        => $item->location,
            'location_label'  => config('menu.locations')[$item->location] ?? $item->location,
            'link_type'       => $item->link_type->value,
            'link_target'     => match ($item->link_type->value) {
                'category' => $item->category ? "Danh mục: {$item->category->name}" : '— (đã xoá) —',
                'url'      => $item->url,
                default    => null,
            },
            'open_in_new_tab' => (bool) $item->open_in_new_tab,
            'is_active'       => (bool) $item->is_active,
            'sort_order'      => $item->sort_order,
            'depth'           => $item->depth,

            // Hoán đổi sort_order với sibling liền trước/sau — xem MenuItemAdminController::reorder().
            'prev_id'         => $prev?->id,
            'prev_sort_order' => $prev?->sort_order ?? $item->sort_order,
            'next_id'         => $next?->id,
            'next_sort_order' => $next?->sort_order ?? $item->sort_order,

            'edit_url'    => route('backend.menu.items.edit', $item),
            'destroy_url' => route('backend.menu.items.destroy', $item),

            'can_update' => $user->can('update', $item),
            'can_delete' => $user->can('delete', $item),

            'children' => $this->mapLevel($item->children, $user),
        ];
    }
}
