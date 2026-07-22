<?php

namespace Modules\Product\Features\CategoryManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Product\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Product\Models\ProductCategory;

/**
 * JSON backend cho Tabulator (dataTree) ở dashboard/products/categories — cùng pattern
 * Modules/Post/.../CategoryApiController: không tìm kiếm → cây đầy đủ cha/con/cháu (children
 * lồng nhau); có tìm kiếm → danh sách phẳng (kết quả khớp không giữ được ngữ cảnh cây). Không
 * phân trang — cây danh mục luôn tải toàn bộ 1 lượt.
 */
class CategoryApiController extends Controller
{
    public function index(
        Request $request,
        ListCategoriesForAdminHandler $flatHandler,
        GetCategoryTreeHandler $treeHandler,
    ): JsonResponse {
        $this->authorize('viewAny', ProductCategory::class);

        $search = $request->string('search')->value() ?: null;
        $user   = $request->user();

        if ($search) {
            $flat = $flatHandler->handle(new ListCategoriesForAdminQuery(search: $search));
            $data = $flat->map(fn (ProductCategory $c) => $this->mapFlat($c, $user))->values();
        } else {
            $tree = $treeHandler->handle(new GetCategoryTreeQuery());
            $data = $tree->map(fn (ProductCategory $c) => $this->mapNode($c, $user))->values();
        }

        return response()->json(['data' => $data]);
    }

    /** @return array<string, mixed> */
    private function mapNode(ProductCategory $node, User $user): array
    {
        $children = $node->children
            ->map(fn (ProductCategory $child) => $this->mapNode($child, $user))
            ->values()
            ->all();

        return [
            ...$this->baseFields($node, $user),
            'children' => $children,
        ];
    }

    /** @return array<string, mixed> */
    private function mapFlat(ProductCategory $category, User $user): array
    {
        return [
            ...$this->baseFields($category, $user),
            'parent_name' => $category->parent?->name,
        ];
    }

    /** @return array<string, mixed> */
    private function baseFields(ProductCategory $category, User $user): array
    {
        return [
            'id'             => $category->id,
            'name'           => $category->name,
            'slug'           => $category->slug,
            'products_count' => $category->products_count,
            'is_active'      => (bool) $category->is_active,
            'edit_url'       => route('backend.products.categories.edit', $category),
            'destroy_url'    => route('backend.products.categories.destroy', $category),
            'can_update'     => $user->can('update', $category),
            'can_delete'     => $user->can('delete', $category),
        ];
    }
}
