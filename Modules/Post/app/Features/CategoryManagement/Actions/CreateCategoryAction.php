<?php

namespace Modules\Post\Features\CategoryManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Models\PostCategory;

class CreateCategoryAction
{
    use AsAction;

    public function handle(CategoryData $data): PostCategory
    {
        return PostCategory::create([
            'parent_id'   => $data->parent_id,
            'name'        => $data->name,
            'slug'        => $this->uniqueSlug($data->name),
            'description' => $data->description,
            'icon'        => $data->icon,
            'color_hex'   => $data->color_hex,
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

        while (PostCategory::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
