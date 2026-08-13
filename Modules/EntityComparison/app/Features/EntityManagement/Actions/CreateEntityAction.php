<?php

namespace Modules\EntityComparison\Features\EntityManagement\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Features\EntityManagement\Data\EntityData;
use Modules\EntityComparison\Models\Entity;
use Modules\EntityComparison\Models\EntityType;

class CreateEntityAction
{
    use AsAction;

    public function handle(EntityData $data, ?UploadedFile $cover = null): Entity
    {
        // spec/Entity_Comparison_Module_Technical_Spec.md §9 — defense in depth: Action tự kiểm
        // tra lại "EntityType chưa xóa mềm", không tin FormRequest đã validate đủ — Action có thể
        // được gọi từ nơi khác ngoài HTTP request (seeder, console command, Action khác).
        EntityType::query()->whereNull('deleted_at')->findOrFail($data->entity_type_id);

        $entity = Entity::create([
            'entity_type_id' => $data->entity_type_id,
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->name),
            'description' => $data->description,
            'is_active' => $data->is_active,
            'sort_order' => $data->sort_order,
            'created_by' => auth()->id(),
        ]);

        if ($cover) {
            $entity->addMedia($cover)->toMediaCollection('cover');
        }

        return $entity;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Entity::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
