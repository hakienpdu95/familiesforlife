<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Actions;

use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Data\EntityTypeData;
use Modules\EntityComparison\Models\EntityType;

class UpdateEntityTypeAction
{
    use AsAction;

    public function handle(EntityType $entityType, EntityTypeData $data, ?UploadedFile $cover = null): EntityType
    {
        // slug bất biến sau khi tạo — đúng convention OcopProduct (route/URL không nên đổi khi
        // đổi tên hiển thị).
        $entityType->update([
            'name' => $data->name,
            'description' => $data->description,
            'icon' => $data->icon,
            'is_active' => $data->is_active,
            'sort_order' => $data->sort_order,
            'updated_by' => auth()->id(),
        ]);

        if ($cover) {
            $entityType->clearMediaCollection('cover');
            $entityType->addMedia($cover)->toMediaCollection('cover');
        }

        return $entityType;
    }
}
