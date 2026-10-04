<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use App\Services\Media\MediaUploadService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopSubject\Models\OcopSubject;

class StoreOcopSubjectDocumentsAction
{
    use AsAction;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(OcopSubject $ocopSubject, array $documents): void
    {
        foreach ($documents as $collection => $files) {
            if (! in_array($collection, OcopSubject::DOCUMENT_COLLECTIONS, true)) {
                continue;
            }

            foreach ($files as $file) {
                $this->mediaUpload->upload($file, $ocopSubject, $collection);
            }
        }
    }
}
