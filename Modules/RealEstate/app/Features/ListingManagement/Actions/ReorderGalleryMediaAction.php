<?php

namespace Modules\RealEstate\Features\ListingManagement\Actions;

use App\Models\Media;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §0 — kéo-thả sắp lại thứ tự ảnh gallery,
 * cập nhật cột `order_column` có sẵn của Spatie Media Library (KHÔNG bảng phụ, §2.4).
 */
class ReorderGalleryMediaAction
{
    use AsAction;

    /** @param string[] $orderedUuids Thứ tự UUID media mới, từ đầu tới cuối. */
    public function handle(RealEstateListing $listing, array $orderedUuids): void
    {
        $mediaByUuid = $listing->getMedia('real_estate_gallery')->keyBy('uuid');

        foreach (array_values($orderedUuids) as $index => $uuid) {
            $mediaByUuid->get($uuid)?->forceFill(['order_column' => $index + 1])->save();
        }
    }
}
