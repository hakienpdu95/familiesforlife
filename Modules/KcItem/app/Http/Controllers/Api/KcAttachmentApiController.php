<?php

namespace Modules\KcItem\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Actions\Backend\DestroyKcAttachmentAction;
use Modules\KcItem\Actions\Backend\StoreKcAttachmentAction;
use Modules\KcItem\Models\KcItem;

class KcAttachmentApiController extends Controller
{
    public function __construct(private readonly MediaUrlService $urlService) {}

    public function store(Request $request, KcItem $kcItem, StoreKcAttachmentAction $action): JsonResponse
    {
        $this->authorize('update', $kcItem);

        $maxMb      = config('kc.attachments.max_file_size_mb', 50);
        $maxItemMb  = config('kc.attachments.max_item_total_mb', 200);
        $allowedExt = implode(',', config('kc.attachments.allowed_extensions', []));

        $request->validate([
            'file' => [
                'required', 'file',
                'max:' . ($maxMb * 1024),
                'mimes:' . $allowedExt,
            ],
        ]);

        // Total size check via media table (bytes)
        $currentTotalBytes = $kcItem->getMedia('attachments')->sum('size');
        $newSizeBytes       = $request->file('file')->getSize();

        if (($currentTotalBytes + $newSizeBytes) > $maxItemMb * 1024 * 1024) {
            return response()->json([
                'message' => 'Tổng dung lượng file đính kèm vượt quá ' . $maxItemMb . 'MB cho phép.',
            ], 422);
        }

        $media = $action->handle($kcItem, $request->file('file'));

        return response()->json([
            'id'           => $media->id,
            'uuid'         => $media->uuid,
            'file_name'    => $media->file_name,
            'file_url'     => $this->urlService->url($media),
            'file_type'    => $media->mime_type,
            'file_size_kb' => (int) ceil($media->size / 1024),
            'delete_url'   => route('backend.api.kc-items.attachments.destroy', [$kcItem, $media->uuid]),
        ], 201);
    }

    public function destroy(KcItem $kcItem, string $attachment, DestroyKcAttachmentAction $action): JsonResponse
    {
        $this->authorize('update', $kcItem);

        $media = Media::where('uuid', $attachment)
            ->where('model_type', KcItem::class)
            ->where('model_id', $kcItem->id)
            ->firstOrFail();

        $action->handle($media);

        return response()->json(['message' => 'Đã xóa file đính kèm.']);
    }
}
