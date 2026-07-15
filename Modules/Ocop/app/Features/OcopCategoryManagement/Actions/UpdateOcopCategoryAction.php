<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopCategoryManagement\Data\OcopCategoryData;
use Modules\Ocop\Models\OcopCategory;

class UpdateOcopCategoryAction
{
    use AsAction;

    public function handle(OcopCategory $category, OcopCategoryData $data): OcopCategory
    {
        $category->update([
            'name'       => $data->name,
            'icon'       => $data->icon,
            'is_active'  => $data->is_active,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        return $category;
    }
}
