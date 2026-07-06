<?php

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Models\Organization;

/**
 * Manage the logo media collection for an organization.
 *
 * Routes (session auth, System_Admin only):
 *   POST   /backend/api/organizations/{organization}/logo
 *   DELETE /backend/api/organizations/{organization}/logo
 */
class OrganizationLogoController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly MediaUrlService $urlService,
    ) {}

    /**
     * POST /backend/api/organizations/{organization}/logo
     */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $collectionCfg = config('media.collections.logo', []);
        $maxKb = $collectionCfg['max_size_kb'] ?? 5120;

        $request->validate([
            'logo' => ['required', 'file', "max:{$maxKb}", 'mimes:jpeg,jpg,png,webp'],
        ]);

        // Upload new logo first, then clean up old ones
        $media = $this->uploadService->upload($request->file('logo'), $organization, 'logo');

        $organization->getMedia('logo')
            ->filter(fn ($m) => $m->id !== $media->id)
            ->each(fn ($m) => $this->uploadService->delete($m));

        return response()->json([
            'uuid'       => $media->uuid,
            'thumb_url'  => $this->urlService->url($media, 'thumb'),
            'medium_url' => $this->urlService->url($media, 'medium'),
            'url'        => $this->urlService->url($media),
        ]);
    }

    /**
     * DELETE /backend/api/organizations/{organization}/logo
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization->getMedia('logo')
            ->each(fn ($m) => $this->uploadService->delete($m));

        return response()->json(['message' => 'Logo đã được xóa.']);
    }
}
