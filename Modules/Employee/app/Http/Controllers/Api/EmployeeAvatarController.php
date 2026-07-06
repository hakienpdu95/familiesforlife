<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;

/**
 * Manage the avatar media collection for a single employee.
 *
 * Routes:
 *   POST   /api/v1/employees/{employee}/avatar   → upload / replace
 *   DELETE /api/v1/employees/{employee}/avatar   → remove
 */
class EmployeeAvatarController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly MediaUrlService $urlService,
    ) {}

    /**
     * POST /api/v1/employees/{employee}/avatar
     *
     * Replaces any existing avatar — upload the new file first,
     * then delete old ones to avoid a gap if the upload fails.
     */
    public function store(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $collectionCfg = config('media.collections.avatar', []);
        $maxKb = $collectionCfg['max_size_kb'] ?? 5120;

        $request->validate([
            'avatar' => ['required', 'file', "max:{$maxKb}", 'mimes:jpeg,jpg,png,webp,gif'],
        ]);

        // Upload new avatar first (safe order: upload → clean up old)
        $media = $this->uploadService->upload($request->file('avatar'), $employee, 'avatar');

        // Remove all previous avatars (keep only the freshly uploaded one)
        $employee->getMedia('avatar')
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
     * DELETE /api/v1/employees/{employee}/avatar
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee->getMedia('avatar')
            ->each(fn ($m) => $this->uploadService->delete($m));

        return response()->json(['message' => 'Avatar đã được xóa.']);
    }
}
