<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FilePondChunkService
{
    private const MAX_CHUNK_BYTES = 8 * 1024 * 1024;

    private const REQUEST_HEADROOM_BYTES = 64 * 1024;

    private const DISK = 'local';

    private const DIR = 'filepond-chunks';

    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public static function chunkSize(): int
    {
        $phpLimit = (int) UploadedFile::getMaxFilesize();
        $limit = $phpLimit > 0 ? min($phpLimit, self::MAX_CHUNK_BYTES) : self::MAX_CHUNK_BYTES;

        return max(256 * 1024, $limit - self::REQUEST_HEADROOM_BYTES);
    }

    public function start(HasMedia&Model $model, string $collection, int $length, int $userId, string $name = 'upload'): string
    {
        $maxKb = (int) config("media.collections.{$collection}.max_size_kb", 51200);
        $allowedMime = config("media.collections.{$collection}.allowed_mime", ['*']);
        $name = $this->safeName($name);

        if (! MediaUploadService::extensionAllowed($name, $allowedMime)) {
            throw ValidationException::withMessages([
                'file' => [MediaUploadService::invalidTypeMessage($name, $allowedMime)],
            ]);
        }

        if ($length <= 0 || $length > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => [MediaUploadService::tooLargeMessage($name, $maxKb)],
            ]);
        }

        $id = (string) Str::uuid();
        $disk = Storage::disk(self::DISK);
        $disk->put($this->partPath($id), '');
        $disk->put($this->metaPath($id), json_encode([
            'user_id' => $userId,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'collection' => $collection,
            'length' => $length,
            'name' => $name,
        ]));

        return $id;
    }

    public function offset(string $id, int $userId): int
    {
        $this->meta($id, $userId);

        return (int) Storage::disk(self::DISK)->size($this->partPath($id));
    }

    public function collection(string $id, int $userId): string
    {
        return $this->meta($id, $userId)['collection'];
    }

    public function append(string $id, int $userId, int $offset, string $chunk): ?Media
    {
        $meta = $this->meta($id, $userId);
        $disk = Storage::disk(self::DISK);
        $partPath = $disk->path($this->partPath($id));

        clearstatcache(true, $partPath);
        $current = filesize($partPath);

        if ($offset !== $current) {
            throw new HttpException(409, 'Upload-Offset không khớp.', null, ['Upload-Offset' => $current]);
        }

        if (strlen($chunk) > self::chunkSize() || $current + strlen($chunk) > $meta['length']) {
            $this->discard($id);
            throw new HttpException(413, 'Chunk vượt kích thước cho phép.');
        }

        file_put_contents($partPath, $chunk, FILE_APPEND | LOCK_EX);
        clearstatcache(true, $partPath);

        if (filesize($partPath) < $meta['length']) {
            return null;
        }

        try {
            $model = $meta['model_type']::query()->findOrFail($meta['model_id']);
            $file = new UploadedFile($partPath, $meta['name'] ?? 'upload', null, null, true);

            return $this->uploadService->upload($file, $model, $meta['collection'], ['uuid' => $id]);
        } catch (FileCannotBeAdded $e) {
            report($e);

            throw ValidationException::withMessages(['file' => [$e->getMessage()]]);
        } finally {
            $this->discard($id);
        }
    }

    public function discardFor(string $id, int $userId): bool
    {
        try {
            $this->meta($id, $userId);
        } catch (HttpException) {
            return false;
        }

        $this->discard($id);

        return true;
    }

    public function discard(string $id): void
    {
        Storage::disk(self::DISK)->delete([$this->partPath($id), $this->metaPath($id)]);
    }

    public function purgeOlderThan(int $hours): int
    {
        $disk = Storage::disk(self::DISK);
        $cutoff = now()->subHours($hours)->getTimestamp();
        $purged = 0;

        foreach ($disk->files(self::DIR) as $path) {
            if (str_ends_with($path, '.json') && $disk->lastModified($path) < $cutoff) {
                $this->discard(basename($path, '.json'));
                $purged++;
            }
        }

        return $purged;
    }

    private function meta(string $id, int $userId): array
    {
        if (! Str::isUuid($id)) {
            throw new HttpException(404, 'Phiên upload không tồn tại.');
        }

        $raw = Storage::disk(self::DISK)->get($this->metaPath($id));
        $meta = $raw ? json_decode($raw, true) : null;

        if (! $meta || (int) $meta['user_id'] !== $userId) {
            throw new HttpException(404, 'Phiên upload không tồn tại.');
        }

        return $meta;
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', rawurldecode($name)));

        return $name !== '' ? $name : 'upload';
    }

    private function partPath(string $id): string
    {
        return self::DIR."/{$id}.part";
    }

    private function metaPath(string $id): string
    {
        return self::DIR."/{$id}.json";
    }
}
