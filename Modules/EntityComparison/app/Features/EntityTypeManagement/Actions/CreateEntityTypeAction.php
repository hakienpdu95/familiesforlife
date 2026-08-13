<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\EntityComparison\Features\EntityTypeManagement\Data\EntityTypeData;
use Modules\EntityComparison\Models\EntityType;

class CreateEntityTypeAction
{
    use AsAction;

    public function handle(EntityTypeData $data, ?UploadedFile $cover = null): EntityType
    {
        $entityType = EntityType::create([
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->name),
            'description' => $data->description,
            'icon' => $data->icon,
            'is_active' => $data->is_active,
            'sort_order' => $data->sort_order,
            'created_by' => auth()->id(),
        ]);

        // spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 3 — Media Library ngay từ đầu.
        if ($cover) {
            $entityType->addMedia($cover)->toMediaCollection('cover');
        }

        return $entityType;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (EntityType::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
