<?php

namespace Modules\RealEstate\Features\ListingManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RealEstate\Models\RealEstateListing;

class DeleteRealEstateListingAction
{
    use AsAction;

    public function handle(RealEstateListing $listing): void
    {
        $listing->delete();
    }
}
