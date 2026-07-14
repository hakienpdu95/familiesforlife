<?php

namespace Modules\Event\Features\EventCategoryManagement\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Features\EventCategoryManagement\Data\EventCategoryData;
use Modules\Event\Models\EventCategory;

class CreateEventCategoryAction
{
    use AsAction;

    public function handle(EventCategoryData $data): EventCategory
    {
        return EventCategory::create([
            'parent_id'  => $data->parent_id,
            'name'       => $data->name,
            'slug'       => $this->uniqueSlug($data->name),
            'icon'       => $data->icon,
            'color_hex'  => $data->color_hex,
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

        while (EventCategory::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
