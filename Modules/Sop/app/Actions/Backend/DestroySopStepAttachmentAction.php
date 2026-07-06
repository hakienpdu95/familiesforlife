<?php

namespace Modules\Sop\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Modules\Sop\Repositories\SopFlowchartRepository;

class DestroySopStepAttachmentAction
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly SopFlowchartRepository $repo,
    ) {}

    public function handle(Media $media, int $sopId): void
    {
        $this->uploadService->delete($media);

        $this->repo->invalidate($sopId);
    }
}
