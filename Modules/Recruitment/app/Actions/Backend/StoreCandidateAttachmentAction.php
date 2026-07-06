<?php

namespace Modules\Recruitment\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcCandidate;

class StoreCandidateAttachmentAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $uploadService) {}

    public function handle(RcCandidate $candidate, UploadedFile $file, array $data): Media
    {
        return $this->uploadService->upload($file, $candidate, 'attachments_private', [
            'file_type'      => $data['file_type'] ?? 'other',
            'application_id' => $data['application_id'] ?? null,
        ]);
    }
}
