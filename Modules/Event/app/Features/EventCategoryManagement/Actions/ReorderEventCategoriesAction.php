<?php

namespace Modules\Event\Features\EventCategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Models\EventCategory;

class ReorderEventCategoriesAction
{
    use AsAction;

    /** @param array<int, int> $order [category_id => sort_order] */
    public function handle(array $order): void
    {
        foreach ($order as $categoryId => $sortOrder) {
            EventCategory::whereKey($categoryId)->update(['sort_order' => $sortOrder]);
        }
    }
}
