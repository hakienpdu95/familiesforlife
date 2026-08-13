<?php

namespace Modules\EntityComparison\Features\EntityManagement\Actions;

use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Features\EntityManagement\Data\EntityData;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Models\EntityType;

class UpdateEntityAction
{
    use AsAction;

    public function handle(Entity $entity, EntityData $data, ?UploadedFile $cover = null): Entity
    {
        // §9 — cùng lý do CreateEntityAction: re-check kể cả khi entity_type_id không đổi so với
        // giá trị hiện tại, đề phòng type đó vừa bị xóa mềm giữa lúc mở form và lúc submit.
        EntityType::query()->whereNull('deleted_at')->findOrFail($data->entity_type_id);

        $entity->update([
            'entity_type_id' => $data->entity_type_id,
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->is_active,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        if ($cover) {
            $entity->clearMediaCollection('cover');
            $entity->addMedia($cover)->toMediaCollection('cover');
        }

        return $entity;
    }
}
