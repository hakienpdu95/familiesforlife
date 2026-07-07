<?php

namespace Modules\Product\Services;

use Modules\Product\Contracts\ProductCatalogContract;
use Modules\Product\Models\Product;

class ProductCatalogService implements ProductCatalogContract
{
    public function find(int $productId): ?Product
    {
        return Product::find($productId);
    }

    public function incrementViewCount(int $productId): void
    {
        Product::whereKey($productId)->increment('view_count');
    }

    public function incrementClickCount(int $productId): void
    {
        Product::whereKey($productId)->increment('total_cta_click_count');
    }

    public function incrementArticleUsageCount(int $productId): void
    {
        Product::whereKey($productId)->increment('used_in_articles_count');
    }

    public function decrementArticleUsageCount(int $productId): void
    {
        Product::whereKey($productId)->where('used_in_articles_count', '>', 0)->decrement('used_in_articles_count');
    }

    public function setArticleUsageCount(int $productId, int $count): void
    {
        Product::whereKey($productId)->update(['used_in_articles_count' => max(0, $count)]);
    }
}
