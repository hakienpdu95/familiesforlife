<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Data\OcopSubjectData;
use Modules\OcopSubject\Models\OcopSubject;

class CreateOcopSubjectAction
{
    use AsAction;

    public function __construct(
        private readonly BuildOcopSubjectAttributesAction $buildAttributes,
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
    ) {}

    public function handle(OcopSubjectData $data): OcopSubject
    {
        $ocopSubject = OcopSubject::create([
            ...$this->buildAttributes->handle($data),
            'created_by' => auth()->id(),
        ]);

        if (! empty($data->media_uuids)) {
            $this->mediaUpload->reassociateFilePondDrafts($ocopSubject, $data->media_uuids, OcopSubject::IMAGE_COLLECTION);
        }

        $this->mediaUpload->reassociateOrphans($ocopSubject, $ocopSubject->storyMediaUuids());

        $story = $this->mediaUrl->refreshEmbeddedImageUrls($ocopSubject->story);
        if ($story !== $ocopSubject->story) {
            $ocopSubject->forceFill(['story' => $story])->saveQuietly();
        }

        return $ocopSubject;
    }
}
