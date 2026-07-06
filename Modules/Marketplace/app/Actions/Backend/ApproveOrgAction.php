<?php

namespace Modules\Marketplace\Actions\Backend;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Models\MktListing;

class ApproveOrgAction
{
    use AsAction;

    public function handle(Organization $org, User $approvedBy): void
    {
        $org->update([
            'status'      => 'active',
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]);

        // Activate all pending_review listings belonging to this org
        MktListing::withoutTenant()
            ->where('org_id', $org->id)
            ->where('poster_type', PosterType::PENDING_ORG->value)
            ->where('status', ListingStatus::PENDING_REVIEW->value)
            ->update([
                'poster_type' => PosterType::ORG->value,
                'status'      => ListingStatus::ACTIVE->value,
            ]);
    }
}
