<?php
namespace Modules\Marketplace\Policies;

use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\ListingVisibility;
use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktListing;

class MktApplicantListingPolicy
{
    public function view(?MktApplicant $applicant, MktListing $listing): bool
    {
        if ($listing->status !== ListingStatus::ACTIVE) {
            return false;
        }
        if ($listing->visibility === ListingVisibility::PUBLIC) {
            return true;
        }
        if ($listing->visibility === ListingVisibility::MEMBERS_ONLY) {
            return $applicant !== null;
        }
        // unlisted — only via direct link (allow if applicant has direct link = always true here)
        return true;
    }

    public function apply(?MktApplicant $applicant, MktListing $listing): bool
    {
        return $applicant !== null && $listing->status === ListingStatus::ACTIVE;
    }
}
