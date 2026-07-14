<?php

namespace Modules\Event\Features\EventCategoryManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Features\EventCategoryManagement\Data\EventCategoryData;
use Modules\Event\Models\EventCategory;

class UpdateEventCategoryAction
{
    use AsAction;

    public function handle(EventCategory $category, EventCategoryData $data): EventCategory
    {
        $category->update([
            'parent_id'  => $data->parent_id,
            'name'       => $data->name,
            'icon'       => $data->icon,
            'color_hex'  => $data->color_hex,
            'is_active'  => $data->is_active,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        return $category;
    }
}
