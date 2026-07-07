<?php

namespace Modules\Product\Features\CategoryManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Product\Features\CategoryManagement\Data\CategoryData;
use Modules\Product\Models\ProductCategory;

class CreateCategoryAction
{
    use AsAction;

    public function handle(CategoryData $data): ProductCategory
    {
        return ProductCategory::create([
            'parent_id'   => $data->parent_id,
            'name'        => $data->name,
            'slug'        => $this->uniqueSlug($data->name),
            'description' => $data->description,
            'icon'        => $data->icon,
            'is_active'   => $data->is_active,
            'sort_order'  => $data->sort_order,
            'created_by'  => auth()->id(),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (ProductCategory::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
