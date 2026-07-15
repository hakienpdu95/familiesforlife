<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopCategoryManagement\Data\OcopCategoryData;
use Modules\Ocop\Models\OcopCategory;

class CreateOcopCategoryAction
{
    use AsAction;

    public function handle(OcopCategoryData $data): OcopCategory
    {
        return OcopCategory::create([
            'name'       => $data->name,
            'slug'       => $this->uniqueSlug($data->name),
            'icon'       => $data->icon,
            'is_active'  => $data->is_active,
            'sort_order' => $data->sort_order,
            'created_by' => auth()->id(),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (OcopCategory::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
