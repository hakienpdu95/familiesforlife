<?php

namespace Modules\Product\Features\CategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CategoryManagement\Data\CategoryData;
use Modules\Product\Models\ProductCategory;

class UpdateCategoryAction
{
    use AsAction;

    public function handle(ProductCategory $category, CategoryData $data): ProductCategory
    {
        $category->update([
            'parent_id'   => $data->parent_id,
            'name'        => $data->name,
            'description' => $data->description,
            'icon'        => $data->icon,
            'is_active'   => $data->is_active,
            'sort_order'  => $data->sort_order,
            'updated_by'  => auth()->id(),
        ]);

        return $category;
    }
}
