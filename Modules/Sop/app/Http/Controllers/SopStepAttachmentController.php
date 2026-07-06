<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sop\Actions\Backend\DestroySopStepAttachmentAction;
use Modules\Sop\Actions\Backend\StoreSopStepAttachmentAction;
use Modules\Sop\Models\SopStep;

class SopStepAttachmentController extends Controller
{
    public function __construct(private readonly MediaUrlService $urlService) {}

    public function index(SopStep $step): JsonResponse
    {
        $this->authorize('view', $step->sop);

        $attachments = $step->getMedia('attachments')
            ->map(fn (Media $m) => $this->formatMedia($m));

        return response()->json($attachments);
    }

    public function store(Request $request, SopStep $step): JsonResponse
    {
        $this->authorize('update', $step->sop);

        $collectionCfg = config('media.collections.attachments', []);
        $maxKb  = $collectionCfg['max_size_kb'] ?? 20480;
        $mimes  = implode(',', config('sop.attachments.allowed_extensions', ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','gif','txt','zip']));

        $request->validate([
            'file' => "required|file|max:{$maxKb}|mimes:{$mimes}",
        ]);

        $media = app(StoreSopStepAttachmentAction::class)->handle($step, $request->file('file'));

        return response()->json($this->formatMedia($media), 201);
    }

    public function destroy(SopStep $step, string $attachment): JsonResponse
    {
        $this->authorize('update', $step->sop);

        $media = Media::where('uuid', $attachment)
            ->where('model_type', SopStep::class)
            ->where('model_id', $step->id)
            ->firstOrFail();

        app(DestroySopStepAttachmentAction::class)->handle($media, $step->sop_id);

        return response()->json(['message' => 'OK']);
    }

    private function formatMedia(Media $media): array
    {
        $sizeKb = (int) ceil($media->size / 1024);

        return [
            'uuid'            => $media->uuid,
            'file_name'       => $media->file_name,
            'file_url'        => $this->urlService->url($media),
            'file_type'       => $media->mime_type,
            'file_size_kb'    => $sizeKb,
            'file_size_label' => $sizeKb >= 1024 ? round($sizeKb / 1024, 1) . ' MB' : "{$sizeKb} KB",
            'alt_text'        => $media->getCustomProperty('alt_text', ''),
            'sort_order'      => $media->order_column,
            'uploaded_at'     => $media->uploaded_at?->format('d/m/Y H:i'),
        ];
    }
}
