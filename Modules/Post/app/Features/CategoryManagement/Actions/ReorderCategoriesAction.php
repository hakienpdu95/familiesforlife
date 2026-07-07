<?php

namespace Modules\Post\Features\CategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostCategory;

class ReorderCategoriesAction
{
    use AsAction;

    /** @param array<int, int> $order [category_id => sort_order] */
    public function handle(array $order): void
    {
        foreach ($order as $categoryId => $sortOrder) {
            PostCategory::whereKey($categoryId)->update(['sort_order' => $sortOrder]);
        }
    }
}
