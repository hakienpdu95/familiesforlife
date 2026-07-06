<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JoditDraft;
use App\Models\Media;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Jodit v4 upload endpoint.
 *
 * Jodit fires uploads immediately on image paste/insert before the form is saved.
 * Files are temporarily attached to JoditDraft model.
 * When the parent content is saved, call MediaUploadService::reassociateOrphans()
 * to move them to the real entity.
 *
 * Response format must match Jodit v4 expected shape exactly.
 */
class MediaJoditUploadController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly MediaUrlService $urlService,
    ) {}

    /**
     * POST /api/v1/media/jodit-upload
     * Headers: X-Context-Type (optional), X-Context-Id (optional)
     *
     * Returns files as [{url, uuid}] — frontend uses uuid for orphan tracking.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ]);

        $draft  = $this->getOrCreateDraft($request);
        $result = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $media = $this->uploadService->upload($file, $draft, 'jodit_content');

                $media->last_touched_at = now();
                $media->save();

                $result[] = [
                    'url'  => $this->urlService->url($media, 'medium'),
                    'uuid' => $media->uuid,
                ];
            } catch (\Throwable $e) {
                Log::error('Jodit upload failed', ['error' => $e->getMessage()]);

                return response()->json([
                    'error'   => true,
                    'message' => 'Upload thất bại: ' . $e->getMessage(),
                    'data'    => ['baseurl' => '', 'files' => []],
                ], 422);
            }
        }

        return response()->json([
            'error'   => false,
            'message' => 'Uploaded successfully',
            'data'    => ['baseurl' => '', 'files' => $result],
        ]);
    }

    /**
     * DELETE /api/v1/media/jodit-upload/{uuid}
     * Called immediately when user removes an image from the editor content.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $media = Media::withoutTenant()
            ->where('uuid', $uuid)
            ->where('collection_name', 'jodit_content')
            ->where('model_type', JoditDraft::class)
            ->firstOrFail();

        $this->uploadService->delete($media);

        return response()->json(['error' => false, 'message' => 'Deleted']);
    }

    /**
     * POST /api/v1/media/jodit-discard
     * Body: { uuids: ["uuid1", "uuid2", ...] }
     *
     * Called via fetch(keepalive) on page unload when user navigates away without saving.
     * Only deletes media still attached to JoditDraft (not yet re-associated to a real entity).
     */
    public function discard(Request $request): JsonResponse
    {
        $uuids = $request->input('uuids', []);
        if (empty($uuids)) {
            return response()->json(['ok' => true]);
        }

        Media::withoutTenant()
            ->whereIn('uuid', $uuids)
            ->where('collection_name', 'jodit_content')
            ->where('model_type', JoditDraft::class)
            ->get()
            ->each(fn (Media $m) => $this->uploadService->delete($m));

        return response()->json(['ok' => true]);
    }

    /**
     * PATCH /api/v1/media/jodit-touch
     * Body: { uuids: ["uuid1", "uuid2"] }
     * Updates last_touched_at to extend orphan TTL for long-form editing sessions.
     */
    public function touch(Request $request): JsonResponse
    {
        $request->validate(['uuids' => ['required', 'array']]);

        Media::withoutTenant()
            ->whereIn('uuid', $request->uuids)
            ->where('collection_name', 'jodit_content')
            ->update(['last_touched_at' => now()]);

        return response()->json(['error' => false]);
    }

    /**
     * GET /api/v1/media/{uuid}/url
     * Refresh a presigned URL (for private files near expiry).
     */
    public function refreshUrl(string $uuid): JsonResponse
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'url' => $this->urlService->url($media),
        ]);
    }

    /**
     * Reuse an existing JoditDraft when editing a known entity (context provided),
     * otherwise create a new one. This prevents one draft-per-image DB bloat.
     */
    private function getOrCreateDraft(Request $request): JoditDraft
    {
        $contextType = $request->header('X-Context-Type') ?: null;
        $contextId   = $request->header('X-Context-Id')   ?: null;

        if ($contextType && $contextId) {
            $existing = JoditDraft::query()
                ->where('user_id', auth()->id())
                ->where('context_type', $contextType)
                ->where('context_id', (string) $contextId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return JoditDraft::create([
            'organization_id' => TenantContext::getOrganizationId(),
            'user_id'         => auth()->id(),
            'context_type'    => $contextType,
            'context_id'      => $contextId,
        ]);
    }
}
