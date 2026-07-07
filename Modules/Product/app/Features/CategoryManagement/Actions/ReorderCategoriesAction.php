<?php

namespace Modules\Product\Features\CategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Models\ProductCategory;

class ReorderCategoriesAction
{
    use AsAction;

    /** @param array<int, int> $order [category_id => sort_order] */
    public function handle(array $order): void
    {
        foreach ($order as $categoryId => $sortOrder) {
            ProductCategory::whereKey($categoryId)->update(['sort_order' => $sortOrder]);
        }
    }
}
