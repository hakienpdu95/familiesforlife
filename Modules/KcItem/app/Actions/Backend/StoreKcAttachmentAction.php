<?php

namespace Modules\KcItem\Actions\Backend;

use App\Models\Media;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcItem;

class StoreKcAttachmentAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $uploadService) {}

    public function handle(KcItem $kcItem, UploadedFile $file): Media
    {
        return $this->uploadService->upload($file, $kcItem, 'attachments');
    }
}
