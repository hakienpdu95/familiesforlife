<?php

namespace Modules\Post\Features\CategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Models\PostCategory;

class UpdateCategoryAction
{
    use AsAction;

    public function handle(PostCategory $category, CategoryData $data): PostCategory
    {
        $category->update([
            'parent_id'   => $data->parent_id,
            'name'        => $data->name,
            'description' => $data->description,
            'icon'        => $data->icon,
            'color_hex'   => $data->color_hex,
            'is_active'   => $data->is_active,
            'sort_order'  => $data->sort_order,
            'updated_by'  => auth()->id(),
        ]);

        return $category;
    }
}
