<?php
namespace Modules\Marketplace\Actions\Portal;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktListing;
use Modules\Marketplace\Models\MktListingBookmark;

class ToggleBookmarkAction
{
    use AsAction;

    public function handle(MktListing $listing, MktApplicant $applicant, ?string $note = null): array
    {
        $existing = MktListingBookmark::where('listing_id', $listing->id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $listing->withoutGlobalScope('tenant')->decrement('bookmark_count');
            return ['bookmarked' => false];
        }

        MktListingBookmark::create([
            'listing_id'   => $listing->id,
            'applicant_id' => $applicant->id,
            'note'         => $note,
        ]);
        $listing->withoutGlobalScope('tenant')->increment('bookmark_count');

        return ['bookmarked' => true];
    }
}
