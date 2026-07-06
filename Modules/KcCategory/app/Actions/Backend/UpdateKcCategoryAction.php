<?php

namespace Modules\KcCategory\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcCategory\Data\Requests\UpdateKcCategoryData;
use Modules\KcCategory\Events\KcCategoryUpdated;
use Modules\KcCategory\Models\KcCategory;

class UpdateKcCategoryAction
{
    use AsAction;

    public function handle(KcCategory $kcCategory, UpdateKcCategoryData $data): KcCategory
    {
        $kcCategory->update([
            'parent_id'   => $data->parent_id,
            'name'        => $data->name,
            'slug'        => $data->slug,
            'description' => $data->description,
            'icon'        => $data->icon,
            'color_hex'   => $data->color_hex,
            'sort_order'  => $data->sort_order,
            'is_active'   => $data->is_active,
            'updated_by'  => auth()->id(),
        ]);

        event(new KcCategoryUpdated($kcCategory));

        return $kcCategory;
    }
}
