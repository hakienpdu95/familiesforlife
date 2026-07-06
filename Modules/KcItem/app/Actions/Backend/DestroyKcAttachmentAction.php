<?php

namespace Modules\KcItem\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;

class DestroyKcAttachmentAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $uploadService) {}

    public function handle(Media $media): void
    {
        $this->uploadService->delete($media);
    }
}
