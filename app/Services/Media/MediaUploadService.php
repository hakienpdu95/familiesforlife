<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\MediaLibrary\HasMedia;

class MediaUploadService
{
    public function __construct(private readonly MediaUrlService $urlService) {}

    /**
     * Upload a file, store it, create a Media record, and run synchronous conversions.
     *
     * @param  array{alt_text?: string, caption?: string}  $options
     */
    public function upload(
        UploadedFile $file,
        HasMedia&Model $model,
        string $collection,
        array $options = []
    ): Media {
        $collectionConfig = $this->collectionConfig($collection);

        $this->validateFile($file, $collectionConfig);

        $disk = $collectionConfig['disk'] ?? config('media.disk', 'public');

        /** @var Media $media */
        $media = $model->addMedia($file)
            ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->usingFileName($file->getClientOriginalName())
            ->withCustomProperties([
                'is_public'      => $collectionConfig['is_public'] ?? true,
                'uploaded_by'    => auth()->id(),
                'module'         => $this->resolveModule($model),
                'alt_text'       => $options['alt_text'] ?? '',
                'caption'        => $options['caption'] ?? '',
                'file_type'      => $options['file_type'] ?? null,
                'application_id' => $options['application_id'] ?? null,
            ])
            ->toMediaCollection($collection, $disk);

        // Set organization_id and uploaded_at explicitly (not handled by Spatie).
        // Only stamp organization_id for tenant-scoped targets — platform-wide content
        // (Post/Ocop/Banner) must NOT be owned by whichever tenant happened to upload it,
        // see spec/Media_Library_Technical_Specification.md §5.1/§7.1.
        $media->organization_id = Media::targetIsTenantScoped($model)
            ? TenantContext::getOrganizationId()
            : null;
        $media->uploaded_at     = now();
        $media->save();

        // Synchronous image conversions (no queue)
        $this->runConversions($media, $collectionConfig['conversions'] ?? []);

        return $media->fresh();
    }

    /**
     * Delete a media record and its associated files on disk.
     * Conversions are stored manually alongside the original (not in Spatie's
     * conversions/ subdir), so we delete them explicitly before the record.
     * Prunes empty ancestor directories up to (not including) the org_id level.
     */
    public function delete(Media $media): void
    {
        $disk     = $media->disk;
        $basePath = rtrim(dirname($media->getPathRelativeToRoot()), '/');

        foreach ($media->generated_conversions ?? [] as $name => $generated) {
            if ($generated) {
                Storage::disk($disk)->delete($basePath . '/' . $name . '.webp');
            }
        }

        $media->delete(); // Spatie deletes original file via model observer

        $this->pruneEmptyAncestors($disk, $basePath);
    }

    /**
     * Walk up the directory tree from $leafDir, deleting each level while empty.
     * Stops at the org_id directory (media/{org_id}) so tenant roots are preserved.
     * Path convention: media/{org_id}/{module}/{entity_type}/{entity_id}/{uuid}
     * → up to 4 levels pruned: uuid, entity_id, entity_type, module.
     */
    public function pruneEmptyAncestors(string $disk, string $leafDir): void
    {
        $path = $leafDir;

        for ($depth = 0; $depth < 4; $depth++) {
            if ($path === '' || $path === '.' || $path === '/') {
                break;
            }

            // Keep org_id dir: path has form "media/{org_id}" (2 segments)
            if (substr_count($path, '/') < 2) {
                break;
            }

            if (! empty(Storage::disk($disk)->files($path)) ||
                ! empty(Storage::disk($disk)->directories($path))) {
                break;
            }

            Storage::disk($disk)->deleteDirectory($path);
            $path = dirname($path);
        }
    }

    /**
     * Delete all media in a collection for the given model.
     */
    public function bulkDelete(HasMedia&Model $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    /**
     * Re-associate Jodit orphan media (collection='jodit_content') to the real entity.
     * Deletes any jodit_content media of this entity that is NOT in $uuids.
     *
     * For FilePond uploads use reassociateFilePondDrafts() — completely separate flow.
     *
     * @param  string[]  $uuids  UUIDs found in the saved HTML content
     */
    public function reassociateOrphans(HasMedia&Model $model, array $uuids): void
    {
        // spec/Media_Library_Technical_Specification.md §5.2/§7.2 — BUGFIX: bản trước `return`
        // sớm khi $uuids rỗng, bỏ qua luôn bước xoá media cũ bên dưới — nghĩa là xoá HẾT ảnh khỏi
        // nội dung (không còn UUID nào) khiến media cũ KHÔNG BAO GIỜ được dọn, orphan vĩnh viễn.
        // Bước reassociate (cần $uuids) tách riêng khỏi bước xoá stale (phải chạy dù $uuids rỗng).
        if (! empty($uuids)) {
            Media::withoutTenant()
                ->whereIn('uuid', $uuids)
                ->where('collection_name', 'jodit_content')
                ->get()
                ->each(function (Media $media) use ($model) {
                    $oldBasePath = rtrim(dirname($media->getPathRelativeToRoot()), '/');
                    $media->model_type = get_class($model);
                    $media->model_id   = $model->getKey();
                    $media->save();
                    $this->moveMediaFiles($media, $oldBasePath);
                });
        }

        // Delete stale jodit_content media for this entity not referenced by current content.
        // whereNotIn() với $uuids rỗng tự động khớp TẤT CẢ (đúng ý muốn: không còn UUID nào cần
        // giữ lại thì mọi media cũ của entity này đều là rác).
        Media::withoutTenant()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', 'jodit_content')
            ->whereNotIn('uuid', $uuids)
            ->get()
            ->each(fn (Media $m) => $this->delete($m));
    }

    /**
     * Re-associate FilePond draft media to the real entity after form save.
     * Deletes any media of this entity in the same collection that is NOT in $uuids.
     *
     * For Jodit uploads use reassociateOrphans() — completely separate flow.
     *
     * @param  string[]  $uuids       UUIDs collected from FilePond bindTo / onUploaded
     * @param  string    $collection  'avatar' | 'logo' | 'thumbnail' | 'cover' | 'attachments' | 'attachments_private'
     */
    public function reassociateFilePondDrafts(HasMedia&Model $model, array $uuids, string $collection): void
    {
        if (empty($uuids)) {
            return;
        }

        Media::withoutTenant()
            ->whereIn('uuid', $uuids)
            ->where('collection_name', $collection)
            ->get()
            ->each(function (Media $media) use ($model) {
                $oldBasePath = rtrim(dirname($media->getPathRelativeToRoot()), '/');
                $media->model_type = get_class($model);
                $media->model_id   = $model->getKey();
                $media->save();
                $this->moveMediaFiles($media, $oldBasePath);
            });

        // Delete stale media for this entity in the same collection not in the new UUID list
        Media::withoutTenant()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->whereNotIn('uuid', $uuids)
            ->get()
            ->each(fn (Media $m) => $this->delete($m));
    }

    /**
     * spec/Media_Library_Technical_Specification.md §5.2/§8 — BUGFIX phát hiện qua verify sống:
     * `MediaPathGenerator` tính path DỰA TRÊN `model_type`/`model_id` HIỆN TẠI của record
     * (`media/{org}/{module}/{entity_type}/{entity_id}/{uuid}`), không phải path đã lưu cố định.
     * Sau khi `reassociateOrphans()`/`reassociateFilePondDrafts()` đổi `model_type`/`model_id`
     * (JoditDraft/FilePondDraft → entity thật), path TÍNH LẠI đổi theo — nhưng file vật lý vẫn
     * nằm ở path CŨ (`moves_media_on_update=false`, Spatie không tự di chuyển). Kết quả:
     * `getFirstMediaUrl()` trả URL trỏ tới file KHÔNG TỒN TẠI (404) — ảnh cover/banner tạo qua
     * form "tạo mới" (FilePondDraft) bị vỡ ngay sau khi lưu, dù DB đã đúng. Phải tự di chuyển
     * file (+ mọi conversion) sang path mới ngay sau khi đổi model_type/model_id.
     */
    private function moveMediaFiles(Media $media, string $oldBasePath): void
    {
        $disk        = $media->disk;
        $newBasePath = rtrim(dirname($media->getPathRelativeToRoot()), '/');

        if ($newBasePath === $oldBasePath || ! Storage::disk($disk)->exists($oldBasePath)) {
            return;
        }

        foreach (Storage::disk($disk)->allFiles($oldBasePath) as $oldFile) {
            $relative = ltrim(substr($oldFile, strlen($oldBasePath)), '/');
            Storage::disk($disk)->move($oldFile, $newBasePath . '/' . $relative);
        }

        $this->pruneEmptyAncestors($disk, $oldBasePath);
    }

    /**
     * Run image conversions synchronously using Intervention Image v4.
     *
     * @param  string[]  $conversions
     */
    private function runConversions(Media $media, array $conversions): void
    {
        if (empty($conversions) || ! str_starts_with($media->mime_type ?? '', 'image/')) {
            return;
        }

        $disk            = $media->disk;
        $basePath        = rtrim(dirname($media->getPathRelativeToRoot()), '/');
        $originalContent = Storage::disk($disk)->get($media->getPathRelativeToRoot());

        if (! $originalContent) {
            return;
        }

        $generatedConversions = [];

        foreach ($conversions as $conversion) {
            $settings = config("media.conversion_settings.{$conversion}");
            if (! $settings) {
                continue;
            }

            try {
                $image = Image::decode($originalContent);

                $originalWidth  = $image->width();
                $originalHeight = $image->height();

                // Skip if resize would require upscaling
                if ($settings['method'] === 'scale' &&
                    $settings['width'] && $originalWidth <= $settings['width']) {
                    $generatedConversions[$conversion] = false;
                    continue;
                }

                // Skip crop if image is smaller than the crop target in both axes
                if ($settings['method'] === 'crop' &&
                    $originalWidth < $settings['width'] &&
                    $originalHeight < $settings['height']) {
                    $generatedConversions[$conversion] = false;
                    continue;
                }

                if ($settings['method'] === 'crop') {
                    $image->cover($settings['width'], $settings['height']);
                } else {
                    $image->scaleDown(width: $settings['width']);
                }

                $encoded = $image->encode(new WebpEncoder($settings['quality']));

                Storage::disk($disk)->put(
                    $basePath . '/' . $conversion . '.webp',
                    (string) $encoded
                );

                $generatedConversions[$conversion] = true;
            } catch (\Throwable $e) {
                Log::error('Media conversion failed', [
                    'media_id'   => $media->id,
                    'conversion' => $conversion,
                    'error'      => $e->getMessage(),
                ]);
                $generatedConversions[$conversion] = false;
            }
        }

        $media->generated_conversions = $generatedConversions;
        $media->save();
    }

    private function validateFile(UploadedFile $file, array $collectionConfig): void
    {
        $maxKb = $collectionConfig['max_size_kb'] ?? 51200;
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => ["File quá lớn. Tối đa {$maxKb} KB."],
            ]);
        }

        $allowedMime = $collectionConfig['allowed_mime'] ?? ['*'];
        if ($allowedMime !== ['*'] && ! in_array($file->getMimeType(), $allowedMime, true)) {
            throw ValidationException::withMessages([
                'file' => ['Loại file không được phép.'],
            ]);
        }
    }

    private function collectionConfig(string $collection): array
    {
        return config("media.collections.{$collection}", [
            'max_size_kb'  => 51200,
            'allowed_mime' => ['*'],
            'is_public'    => true,
            'conversions'  => [],
        ]);
    }

    private function resolveModule(Model $model): string
    {
        $class = get_class($model);
        if (str_starts_with($class, 'Modules\\')) {
            return strtolower(explode('\\', $class)[1] ?? 'core');
        }
        return match ($class) {
            \App\Models\JoditDraft::class => 'jodit',
            default                       => 'core',
        };
    }
}
