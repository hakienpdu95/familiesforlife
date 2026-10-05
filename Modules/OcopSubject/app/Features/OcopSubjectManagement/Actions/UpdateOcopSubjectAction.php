<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\SyncOcopSubjectSnapshotAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Data\OcopSubjectData;
use Modules\OcopSubject\Models\OcopSubject;

class UpdateOcopSubjectAction
{
    use AsAction;

    public function __construct(
        private readonly BuildOcopSubjectAttributesAction $buildAttributes,
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
        private readonly SyncOcopSubjectSnapshotAction $syncProducts,
    ) {}

    public function handle(OcopSubject $ocopSubject, OcopSubjectData $data): OcopSubject
    {
        $ocopSubject->update([
            ...$this->buildAttributes->handle($data),
            'updated_by' => auth()->id(),
        ]);

        if (! empty($data->remove_media_uuids)) {
            $ocopSubject->media()
                ->whereIn('collection_name', [OcopSubject::IMAGE_COLLECTION, ...OcopSubject::DOCUMENT_COLLECTIONS])
                ->whereIn('uuid', $data->remove_media_uuids)
                ->get()
                ->each(fn ($media) => $this->mediaUpload->delete($media));
        }

        if ($ocopSubject->wasChanged(['name', 'address', 'province_code', 'ward_code'])) {
            $this->syncProducts->handle($ocopSubject);
        }

        $this->mediaUpload->reassociateOrphans($ocopSubject, $ocopSubject->storyMediaUuids());

        $story = $this->mediaUrl->refreshEmbeddedImageUrls($ocopSubject->story);
        if ($story !== $ocopSubject->story) {
            $ocopSubject->forceFill(['story' => $story])->saveQuietly();
        }

        return $ocopSubject;
    }
}
