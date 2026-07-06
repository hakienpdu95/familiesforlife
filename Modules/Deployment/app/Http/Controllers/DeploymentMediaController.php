<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Deployment\Models\DeploymentTarget;

/**
 * Upload / delete media files to Organization MediaLibrary (Spatie).
 *
 * Collections:
 *   legal_docs    — ĐKKD, CCCD, chứng nhận chất lượng
 *   field_photos  — ảnh thực địa vùng sản xuất
 *   history_files — file Excel lịch sử hoạt động
 *   donvi_files   — file Excel đơn vị chi tiết
 */
class DeploymentMediaController extends Controller
{
    private const ALLOWED_COLLECTIONS = ['legal_docs', 'field_photos', 'history_files', 'donvi_files'];

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(Request $request, DeploymentTarget $target): JsonResponse
    {
        $this->authorize('view', $target);

        $org        = $target->targetOrganization;
        $collection = $request->query('collection', 'legal_docs');

        if (! $org || ! in_array($collection, self::ALLOWED_COLLECTIONS, true)) {
            return response()->json(['media' => []]);
        }

        $media = $org->getMedia($collection)->map(fn($m) => [
            'id'         => $m->id,
            'name'       => $m->file_name,
            'url'        => $m->getUrl(),
            'size'       => $m->human_readable_size,
            'created_at' => $m->created_at->format('d/m/Y'),
            'custom'     => $m->custom_properties,
        ]);

        return response()->json(['media' => $media]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request, DeploymentTarget $target): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $target);

        $collection = $request->input('collection', 'legal_docs');

        if (! in_array($collection, self::ALLOWED_COLLECTIONS, true)) {
            return $this->errorResponse($request, 'Collection không hợp lệ.');
        }

        $request->validate([
            'file'        => 'required|file|max:20480',  // 20 MB
            'doc_type'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $org = $target->targetOrganization;
        if (! $org) {
            return $this->errorResponse($request, 'Target chưa liên kết tổ chức.');
        }

        $media = $org->addMedia($request->file('file'))
            ->withCustomProperties([
                'doc_type'         => $request->input('doc_type'),
                'description'      => $request->input('description'),
                'uploaded_by'      => auth()->id(),
                'deployment_target_id' => $target->id,
            ])
            ->toMediaCollection($collection);

        if ($request->expectsJson()) {
            return response()->json([
                'id'   => $media->id,
                'name' => $media->file_name,
                'url'  => $media->getUrl(),
            ]);
        }

        $vertical = $request->attributes->get('_vertical');

        return redirect()
            ->route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id])
            ->with('success', 'Đã upload ' . $media->file_name . '.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(Request $request, DeploymentTarget $target, int $mediaId): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $target);

        $org = $target->targetOrganization;
        if (! $org) {
            return $this->errorResponse($request, 'Target chưa liên kết tổ chức.');
        }

        $media = $org->getMedia()->firstWhere('id', $mediaId);
        if (! $media) {
            return $this->errorResponse($request, 'File không tồn tại hoặc không thuộc target này.');
        }

        $name = $media->file_name;
        $media->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => $mediaId]);
        }

        $vertical = $request->attributes->get('_vertical');

        return redirect()
            ->route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id])
            ->with('success', "Đã xóa {$name}.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function errorResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return back()->withErrors($message);
    }
}
