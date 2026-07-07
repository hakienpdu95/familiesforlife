<?php

namespace Modules\Product\Features\CategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\ProductCategory;

class DeleteCategoryAction
{
    use AsAction;

    /**
     * @throws \RuntimeException Nếu danh mục còn danh mục con.
     *
     * Không chặn xoá khi còn sản phẩm gán trực tiếp — `products.category_id` là
     * `nullOnDelete()` (docs/product-catalog-spec.md §6.2): xoá danh mục không kéo
     * theo hệ luỵ gì tới sản phẩm, sản phẩm chỉ mất phân loại và cần re-categorize.
     */
    public function handle(ProductCategory $category): void
    {
        if ($category->children()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn danh mục con — hãy chuyển/xoá danh mục con trước.');
        }

        $category->delete();
    }
}
