<?php

namespace Modules\Post\Features\CategoryManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Post\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Post\Models\PostCategory;

/**
 * JSON backend cho Tabulator (dataTree) ở dashboard/posts/categories — không phân trang
 * (cây danh mục luôn tải toàn bộ 1 lượt, đúng hành vi cũ). Không tìm kiếm → cây đầy đủ cha/
 * con/cháu (children lồng nhau, đúng cấp bậc); có tìm kiếm → danh sách phẳng (kết quả khớp
 * không giữ được ngữ cảnh cây — cùng quyết định đã có ở CategoryAdminController::index()).
 */
class CategoryApiController extends Controller
{
    public function index(
        Request $request,
        ListCategoriesForAdminHandler $flatHandler,
        GetCategoryTreeHandler $treeHandler,
    ): JsonResponse {
        $this->authorize('viewAny', PostCategory::class);

        $search = $request->string('search')->value() ?: null;
        $user   = $request->user();

        if ($search) {
            $flat = $flatHandler->handle(new ListCategoriesForAdminQuery(search: $search));
            $data = $flat->map(fn (PostCategory $c) => $this->mapFlat($c, $user))->values();
        } else {
            $tree = $treeHandler->handle(new GetCategoryTreeQuery());
            $data = $tree->map(fn (PostCategory $c) => $this->mapNode($c, $user))->values();
        }

        return response()->json(['data' => $data]);
    }

    /** @return array<string, mixed> */
    private function mapNode(PostCategory $node, User $user): array
    {
        $children = $node->children
            ->map(fn (PostCategory $child) => $this->mapNode($child, $user))
            ->values()
            ->all();

        return [
            ...$this->baseFields($node, $user),
            'children' => $children,
        ];
    }

    /** @return array<string, mixed> */
    private function mapFlat(PostCategory $category, User $user): array
    {
        return [
            ...$this->baseFields($category, $user),
            'parent_name' => $category->parent?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function baseFields(PostCategory $category, User $user): array
    {
        return [
            'id'             => $category->id,
            'name'           => $category->name,
            'slug'           => $category->slug,
            'color_hex'      => $category->color_hex,
            'articles_count' => $category->articles_count,
            'is_active'      => (bool) $category->is_active,
            'edit_url'       => route('backend.post.categories.edit', $category),
            'destroy_url'    => route('backend.post.categories.destroy', $category),
            'can_update'     => $user->can('update', $category),
            'can_delete'     => $user->can('delete', $category),
        ];
    }
}
