<?php
namespace Modules\Marketplace\Actions\Portal;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\StoreApplicationData;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Models\MktApplication;
use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktListing;

class ApplyListingAction
{
    use AsAction;

    public function handle(MktListing $listing, MktApplicant $applicant, StoreApplicationData $data): MktApplication
    {
        // Increment application_count on the listing
        $listing->increment('application_count');

        return MktApplication::create([
            'listing_id'      => $listing->id,
            'applicant_id'    => $applicant->id,
            'status'          => ApplicationStatus::Submitted->value,
            'cover_letter'    => $data->cover_letter,
            'expected_salary' => $data->expected_salary,
            'available_from'  => $data->available_from,
            'portfolio_url'   => $data->portfolio_url,
        ]);
    }
}
