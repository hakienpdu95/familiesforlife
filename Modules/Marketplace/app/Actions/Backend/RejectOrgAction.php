<?php

namespace Modules\Marketplace\Actions\Backend;

use App\Shared\Tenancy\Models\Organization;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Models\MktListing;

class RejectOrgAction
{
    use AsAction;

    public function handle(Organization $org, ?string $reason = null): void
    {
        $org->update(['status' => 'rejected']);

        // Close all pending listings for this org
        MktListing::withoutTenant()
            ->where('org_id', $org->id)
            ->where('poster_type', PosterType::PENDING_ORG->value)
            ->where('status', ListingStatus::PENDING_REVIEW->value)
            ->update(['status' => ListingStatus::CLOSED->value]);
    }
}
