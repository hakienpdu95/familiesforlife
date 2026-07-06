<?php

namespace App\Services\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Generates storage paths following the convention:
 * media/{org_id}/{module}/{entity_type}/{entity_id}/{uuid}/
 *
 * module is derived from the model's namespace segment (e.g. Modules\Sop\... → sop).
 * entity_type is Str::snake(class_basename($model)).
 */
class MediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media) . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media) . '/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media) . '/';
    }

    private function basePath(Media $media): string
    {
        $orgId      = $media->organization_id ?? 0;
        $module     = $this->resolveModule($media->model_type);
        $entityType = Str::snake(class_basename($media->model_type));
        $entityId   = $media->model_id;
        $uuid       = $media->uuid;

        return "media/{$orgId}/{$module}/{$entityType}/{$entityId}/{$uuid}";
    }

    private function resolveModule(string $modelType): string
    {
        // Modules\Sop\Models\SopStep → sop
        if (str_starts_with($modelType, 'Modules\\')) {
            return Str::lower(explode('\\', $modelType)[1] ?? 'core');
        }

        // App-level models
        return match ($modelType) {
            \App\Models\JoditDraft::class => 'jodit',
            default                       => 'core',
        };
    }
}
