<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\SidebarItemData;
use Modules\BusinessBlueprint\Models\BlueprintSidebarItem;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertSidebarItemAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(SidebarItemData $data, ?BlueprintSidebarItem $sidebarItem = null): BlueprintSidebarItem
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'parent_id'             => $data->parent_id,
            'module_key'            => $data->module_key,
            'label'                 => $data->label,
            'icon'                  => $data->icon,
            'sort_order'            => $data->sort_order,
        ];

        if (! $sidebarItem) {
            return BlueprintSidebarItem::create($attributes);
        }

        $sidebarItem->update($attributes);

        return $sidebarItem->fresh();
    }
}
