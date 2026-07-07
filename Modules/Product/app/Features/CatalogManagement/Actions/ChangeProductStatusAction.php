<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;

class ChangeProductStatusAction
{
    use AsAction;

    /**
     * Chuyển active ⇄ inactive ⇄ discontinued ⇄ out_of_stock — không guard tham chiếu,
     * đây chính là cách "xoá mềm nghiệp vụ" đúng đắn khi sản phẩm còn được bài viết dùng
     * (docs/product-catalog-spec.md §10.2), luôn cho phép đổi status.
     */
    public function handle(Product $product, ProductStatus $status): Product
    {
        $product->update([
            'status'     => $status,
            'updated_by' => auth()->id(),
        ]);

        return $product;
    }
}
