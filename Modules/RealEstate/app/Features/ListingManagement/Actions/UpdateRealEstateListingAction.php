<?php

namespace Modules\RealEstate\Features\ListingManagement\Actions;

use App\Services\Media\MediaUploadService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RealEstate\Features\ListingManagement\Actions\Concerns\BuildsListingAttributes;
use Modules\RealEstate\Features\ListingManagement\Data\RealEstateListingData;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * slug KHÔNG đổi lại khi update (đã là URL công khai — đúng mẫu UpdateProductAction, không
 * đưa 'slug' vào mảng update).
 */
class UpdateRealEstateListingAction
{
    use AsAction;
    use BuildsListingAttributes;

    public function __construct(private readonly MediaUploadService $mediaUpload) {}

    public function handle(RealEstateListing $listing, RealEstateListingData $data): RealEstateListing
    {
        return DB::transaction(function () use ($listing, $data) {
            $listing->update(array_merge($this->buildAttributes($data), [
                'updated_by' => auth()->id(),
            ]));

            if (! empty($data->gallery_media_uuids)) {
                $this->mediaUpload->reassociateFilePondDrafts($listing, $data->gallery_media_uuids, 'real_estate_gallery');
            }

            return $listing;
        });
    }
}
