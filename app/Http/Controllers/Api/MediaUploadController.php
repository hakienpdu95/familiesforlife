<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilePondDraft;
use App\Models\Media;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;

/**
 * FilePond upload endpoint.
 *
 * Handles structured form-field uploads (avatar, logo, thumbnail, cover, attachments).
 * Jodit inline images are handled separately by MediaJoditUploadController.
 *
 * Two association modes:
 *  - Direct: X-Context-Type + X-Context-Id headers → attach immediately to existing entity (edit form)
 *  - Draft:  No context → attach to FilePondDraft temp holder, reassociate on form save (create form)
 *
 * FilePond server protocol:
 *  process → POST   /api/v1/media/upload         returns JSON {uuid, url, thumb_url, original}
 *  revert  → DELETE /api/v1/media/upload/{uuid}  only allowed while still in draft state
 */
class MediaUploadController extends Controller
{
    /**
     * Collections FilePond is allowed to upload to.
     * jodit_content is exclusively managed by MediaJoditUploadController.
     */
    private const ALLOWED_COLLECTIONS = [
        'avatar', 'logo', 'thumbnail', 'cover',
        'attachments', 'attachments_private',
    ];

    /**
     * Collections that hold exactly one file per entity.
     * When a new file is uploaded directly to a real entity, old files in the same
     * collection are deleted automatically after the new upload succeeds.
     */
    private const SINGLE_FILE_COLLECTIONS = ['avatar', 'logo', 'thumbnail', 'cover'];

    /**
     * entity_type (X-Context-Type header) → fully-qualified model class.
     * Only models that implement HasMedia + HasTenantMedia are listed here.
     * Add new models as they adopt HasTenantMedia.
     */
    private const ENTITY_MAP = [
        'employee'                => \Modules\Employee\Models\Employee::class,
        'organization'            => \Modules\Organization\Models\Organization::class,
        'mkt_applicant'           => \Modules\Marketplace\Models\MktApplicant::class,
        'mkt_applicant_portfolio' => \Modules\Marketplace\Models\MktApplicantPortfolio::class,
        'sop_step'                => \Modules\Sop\Models\SopStep::class,
        'kc_item'                 => \Modules\KcItem\Models\KcItem::class,
        'rc_candidate'            => \Modules\Recruitment\Models\RcCandidate::class,
    ];

    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly MediaUrlService    $urlService,
    ) {}

    /**
     * POST /api/v1/media/upload
     *
     * Required headers:
     *   X-Collection   — avatar | logo | thumbnail | cover | attachments | attachments_private
     * Optional headers:
     *   X-Context-Type — entity_type string (e.g. 'employee')
     *   X-Context-Id   — entity numeric ID
     */
    public function store(Request $request): JsonResponse
    {
        $collection = $request->header('X-Collection', '');

        if (! in_array($collection, self::ALLOWED_COLLECTIONS, true)) {
            return response()->json(['message' => 'Collection không hợp lệ.'], 422);
        }

        $collectionConfig = config("media.collections.{$collection}", []);

        $request->validate([
            'file' => ['required', 'file', 'max:' . ($collectionConfig['max_size_kb'] ?? 10240)],
        ]);

        $file        = $request->file('file');
        $allowedMime = $collectionConfig['allowed_mime'] ?? ['*'];

        if ($allowedMime !== ['*'] && ! in_array($file->getMimeType(), $allowedMime, true)) {
            return response()->json(['message' => 'Loại file không được hỗ trợ.'], 422);
        }

        $model = $this->resolveModel($request);

        try {
            $media = $this->uploadService->upload($file, $model, $collection);

            // Single-file collection + real entity → delete old files after successful upload
            if (! ($model instanceof FilePondDraft) &&
                in_array($collection, self::SINGLE_FILE_COLLECTIONS, true)) {
                $this->deleteOldMedia($model, $collection, $media->uuid);
            }

            return response()->json([
                'uuid'      => $media->uuid,
                'url'       => $this->urlService->url($media, 'medium') ?: $this->urlService->url($media),
                'thumb_url' => $this->urlService->url($media, 'thumb')  ?: $this->urlService->url($media),
                'original'  => $this->urlService->url($media),
            ]);
        } catch (\Throwable $e) {
            Log::error('FilePond upload failed', [
                'collection' => $collection,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Upload thất bại: ' . $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/v1/media/upload/{uuid}
     *
     * FilePond revert — two cases:
     *
     * 1. Draft (model_type = FilePondDraft): delete unconditionally — user cancels
     *    their pending upload before form save.
     *
     * 2. Direct entity (edit form with X-Context-Type/Id): allowed only when the media
     *    was uploaded by the current user AND collection is in our managed list.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $media = Media::withoutTenant()
            ->where('uuid', $uuid)
            ->where('organization_id', TenantContext::getOrganizationId())
            ->first();

        if (! $media) {
            return response()->json(['message' => 'File không tồn tại.'], 404);
        }

        $isDraft    = $media->model_type === FilePondDraft::class;
        $uploadedBy = $media->getCustomProperty('uploaded_by');

        // Non-draft files: only the uploader may revert, and only for managed collections
        if (! $isDraft && (int) $uploadedBy !== auth()->id()) {
            return response()->json(['message' => 'Không có quyền xóa file này.'], 403);
        }

        if (! $isDraft && ! in_array($media->collection_name, self::ALLOWED_COLLECTIONS, true)) {
            return response()->json(['message' => 'Không thể hủy upload này.'], 403);
        }

        $this->uploadService->delete($media);

        return response()->json(['ok' => true]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Resolve which Eloquent model to attach the upload to.
     * Direct association takes priority when context headers are provided and the
     * entity exists in ENTITY_MAP. Falls back to FilePondDraft for create forms.
     */
    private function resolveModel(Request $request): Model&HasMedia
    {
        $contextType = $request->header('X-Context-Type') ?: null;
        $contextId   = $request->header('X-Context-Id')   ?: null;

        if ($contextType && $contextId && isset(self::ENTITY_MAP[$contextType])) {
            $class = self::ENTITY_MAP[$contextType];
            $model = $class::query()->find((int) $contextId);

            if ($model instanceof HasMedia) {
                return $model;
            }
        }

        return $this->getOrCreateDraft($contextType, $contextId);
    }

    private function getOrCreateDraft(?string $contextType, ?string $contextId): FilePondDraft
    {
        if ($contextType && $contextId) {
            $existing = FilePondDraft::query()
                ->where('user_id', auth()->id())
                ->where('context_type', $contextType)
                ->where('context_id', (string) $contextId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return FilePondDraft::create([
            'organization_id' => TenantContext::getOrganizationId(),
            'user_id'         => auth()->id(),
            'context_type'    => $contextType,
            'context_id'      => $contextId,
        ]);
    }

    /**
     * Delete all other media in the same collection for the model,
     * keeping only the newly uploaded one.
     * Called only for single-file collections after a successful upload.
     */
    private function deleteOldMedia(Model&HasMedia $model, string $collection, string $keepUuid): void
    {
        Media::withoutTenant()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->where('uuid', '!=', $keepUuid)
            ->get()
            ->each(fn (Media $m) => $this->uploadService->delete($m));
    }
}
