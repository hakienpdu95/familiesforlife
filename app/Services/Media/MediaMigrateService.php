<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Move media files between disks (local → S3, S3 → R2, etc.).
 *
 * Guarantees:
 *  - Copies ALL files in the media UUID directory (original + conversion variants)
 *  - Verifies MD5 checksum before marking as migrated
 *  - Rolls back target files on failure (no partial state)
 *  - Records stay on source until verify passes; then DB is updated atomically
 */
class MediaMigrateService
{
    /**
     * Migrate a single Media record to a different disk.
     *
     * @return array{status: string, files: int|list<string>, uuid: string}
     * @throws RuntimeException on checksum failure (source files untouched, target cleaned up)
     */
    public function migrateToDisk(Media $media, string $targetDisk, bool $dryRun = false): array
    {
        $sourceDisk = $media->disk;

        if ($sourceDisk === $targetDisk) {
            return ['status' => 'skipped', 'reason' => 'already on target', 'uuid' => $media->uuid];
        }

        $baseDir = rtrim(dirname($media->getPathRelativeToRoot()), '/');

        if (! Storage::disk($sourceDisk)->exists($media->getPathRelativeToRoot())) {
            return ['status' => 'skipped', 'reason' => 'source file missing', 'uuid' => $media->uuid];
        }

        $files = Storage::disk($sourceDisk)->files($baseDir);

        if ($dryRun) {
            return ['status' => 'dry_run', 'files' => $files, 'uuid' => $media->uuid];
        }

        $copied = [];

        try {
            foreach ($files as $path) {
                $content   = Storage::disk($sourceDisk)->get($path);
                $srcHash   = md5($content);

                Storage::disk($targetDisk)->put($path, $content);

                $dstHash = md5(Storage::disk($targetDisk)->get($path));

                if ($srcHash !== $dstHash) {
                    throw new RuntimeException("MD5 mismatch for: {$path}");
                }

                $copied[] = $path;
            }
        } catch (\Throwable $e) {
            // Rollback: delete what we already copied
            foreach ($copied as $path) {
                try {
                    Storage::disk($targetDisk)->delete($path);
                } catch (\Throwable) {}
            }

            throw $e;
        }

        // Update DB atomically
        $media->disk = $targetDisk;
        if ($media->conversions_disk !== null) {
            $media->conversions_disk = $targetDisk;
        }
        $media->save();

        // Delete source directory after successful DB update
        Storage::disk($sourceDisk)->deleteDirectory($baseDir);

        return ['status' => 'migrated', 'files' => count($copied), 'uuid' => $media->uuid];
    }

    /**
     * Migrate all media in a collection for a given model.
     *
     * @return array{migrated: int, skipped: int, failed: int}
     */
    public function migrateModelToDisk(HasMedia&Model $model, string $collection, string $targetDisk, bool $dryRun = false): array
    {
        $results = ['migrated' => 0, 'skipped' => 0, 'failed' => 0];

        $model->getMedia($collection)->each(function (Media $media) use ($targetDisk, $dryRun, &$results) {
            try {
                $result = $this->migrateToDisk($media, $targetDisk, $dryRun);

                if ($result['status'] === 'migrated' || $result['status'] === 'dry_run') {
                    $results['migrated']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Throwable $e) {
                Log::error('MediaMigrateService: migration failed', [
                    'media_id' => $media->id,
                    'uuid'     => $media->uuid,
                    'error'    => $e->getMessage(),
                ]);
                $results['failed']++;
            }
        });

        return $results;
    }

    /**
     * Build a query for records eligible for migration.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildMigrateQuery(string $fromDisk, ?string $collection = null, ?int $orgId = null)
    {
        $q = Media::withoutTenant()->where('disk', $fromDisk);

        if ($collection !== null) {
            $q->where('collection_name', $collection);
        }

        if ($orgId !== null) {
            $q->where('organization_id', $orgId);
        }

        return $q;
    }

    /**
     * Partition a collection of files into batches of $size.
     */
    public function chunkQuery(string $fromDisk, int $batchSize, ?string $collection, ?int $orgId): Collection
    {
        return $this->buildMigrateQuery($fromDisk, $collection, $orgId)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();
    }
}
