<?php

namespace Modules\Sop\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Repositories\SopFlowchartRepository;

class StoreSopStepAttachmentAction
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
        private readonly SopFlowchartRepository $repo,
    ) {}

    public function handle(SopStep $step, UploadedFile $file): Media
    {
        $media = $this->uploadService->upload($file, $step, 'attachments');

        $this->repo->invalidate($step->sop_id);

        return $media;
    }
}
