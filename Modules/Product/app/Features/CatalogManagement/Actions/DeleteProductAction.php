<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CatalogManagement\Exceptions\ProductStillReferencedException;
use Modules\Product\Models\Product;

class DeleteProductAction
{
    use AsAction;

    /**
     * Guard bắt buộc (docs/product-catalog-spec.md §10.2): chặn xoá cứng nếu còn bài viết
     * tham chiếu — đọc trực tiếp cột `used_in_articles_count` của chính bảng `products`,
     * không cần cross-module query (counter được module Post duy trì qua Contract).
     *
     * @throws ProductStillReferencedException
     */
    public function handle(Product $product): void
    {
        if ($product->used_in_articles_count > 0) {
            throw new ProductStillReferencedException($product->used_in_articles_count);
        }

        $product->delete();
    }
}
