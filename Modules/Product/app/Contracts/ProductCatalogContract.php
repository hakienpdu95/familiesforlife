<?php

namespace Modules\Product\Contracts;

use Modules\Product\Models\Product;

/**
 * Interface công khai cho module khác (Post, ...) gọi — không query thẳng model
 * `Product` để giữ khớp nối lỏng (docs/product-catalog-spec.md §11.2).
 */
interface ProductCatalogContract
{
    public function find(int $productId): ?Product;

    public function incrementViewCount(int $productId): void;

    public function incrementClickCount(int $productId): void;

    // Usage-count rollup — News/Post là bên duy nhất gọi các method này, Product không tự tính
    public function incrementArticleUsageCount(int $productId): void;

    public function decrementArticleUsageCount(int $productId): void;

    public function setArticleUsageCount(int $productId, int $count): void;
}
