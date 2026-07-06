<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Actions\Backend\StoreCandidateAttachmentAction;
use Modules\Recruitment\Enums\AttachmentFileType;
use Modules\Recruitment\Models\RcCandidate;

class CandidateAttachmentController extends Controller
{
    public function __construct(private readonly MediaUrlService $urlService) {}

    public function store(Request $request, RcCandidate $candidate, StoreCandidateAttachmentAction $action): JsonResponse
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'file'           => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,webp,txt,csv,zip'],
            'file_type'      => ['required', 'string', 'in:' . implode(',', array_column(AttachmentFileType::cases(), 'value'))],
            'application_id' => ['nullable', 'integer', 'exists:rc_applications,id'],
        ]);

        $media = $action->handle($candidate, $request->file('file'), $validated);

        $fileTypeEnum = AttachmentFileType::tryFrom($media->getCustomProperty('file_type', 'other'));

        return response()->json([
            'message'    => 'Đã tải lên file thành công',
            'attachment' => [
                'id'          => $media->id,
                'uuid'        => $media->uuid,
                'file_name'   => $media->file_name,
                'file_url'    => $this->urlService->url($media),
                'file_type'   => $fileTypeEnum?->value,
                'file_label'  => $fileTypeEnum?->label(),
                'file_size'   => $this->formatSize((int) ceil($media->size / 1024)),
                'uploaded_by' => auth()->user()?->name,
                'uploaded_at' => $media->uploaded_at?->format('d/m/Y H:i'),
                'delete_url'  => route('backend.candidates.attachments.destroy', [$candidate, $media->uuid]),
            ],
        ]);
    }

    public function destroy(RcCandidate $candidate, string $attachment): JsonResponse
    {
        $this->authorize('update', $candidate);

        $media = Media::where('uuid', $attachment)
            ->where('model_type', RcCandidate::class)
            ->where('model_id', $candidate->id)
            ->firstOrFail();

        $media->delete();

        return response()->json(['message' => 'Đã xóa file']);
    }

    private function formatSize(int $kb): string
    {
        return $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : "{$kb} KB";
    }
}
