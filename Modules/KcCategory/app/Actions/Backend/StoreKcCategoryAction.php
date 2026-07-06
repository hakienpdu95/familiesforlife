<?php

namespace Modules\KcCategory\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcCategory\Data\Requests\StoreKcCategoryData;
use Modules\KcCategory\Events\KcCategoryCreated;
use Modules\KcCategory\Models\KcCategory;

class StoreKcCategoryAction
{
    use AsAction;

    public function handle(StoreKcCategoryData $data): KcCategory
    {
        $kcCategory = KcCategory::create([
            'uuid'            => Str::uuid(),
            'organization_id' => $data->organization_id,
            'parent_id'       => $data->parent_id,
            'name'        => $data->name,
            'slug'        => $data->slug,
            'description' => $data->description,
            'icon'        => $data->icon,
            'color_hex'   => $data->color_hex,
            'sort_order'  => $data->sort_order,
            'is_active'   => $data->is_active,
            'created_by'  => auth()->id(),
        ]);

        event(new KcCategoryCreated($kcCategory));

        return $kcCategory;
    }
}
