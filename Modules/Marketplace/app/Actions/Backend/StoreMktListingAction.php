<?php

namespace Modules\Marketplace\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\StoreMktListingData;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Events\MktListingCreated;
use Modules\Marketplace\Models\MktListing;

class StoreMktListingAction
{
    use AsAction;

    public function handle(StoreMktListingData $data, int $postedBy): MktListing
    {
        $listing = MktListing::create([
            'org_id'              => $data->organization_id ?? auth()->user()->organization_id ?? TenantContext::getOrganizationId(),
            'posted_by'           => $postedBy,
            'poster_type'         => PosterType::ORG->value,
            'listing_type'        => $data->listing_type->value,
            'title'               => $data->title,
            'description'         => $data->description,
            'requirements'        => $data->requirements,
            'benefits'            => $data->benefits,
            'status'              => ListingStatus::ACTIVE->value,
            'visibility'          => $data->visibility->value,
            'work_type'           => $data->work_type->value,
            'employment_type'     => $data->employment_type?->value,
            'experience_level'    => $data->experience_level->value,
            'salary_min'          => $data->salary_min,
            'salary_max'          => $data->salary_max,
            'salary_currency'     => $data->salary_currency,
            'salary_is_negotiable' => $data->salary_is_negotiable,
            'salary_is_visible'   => $data->salary_is_visible,
            'budget_min'          => $data->budget_min,
            'budget_max'          => $data->budget_max,
            'duration_days'       => $data->duration_days,
            'location'            => $data->location,
            'department_id'       => $data->department_id,
            'position_id'         => $data->position_id,
            'headcount'           => $data->headcount,
            'expire_at'           => $data->expire_at,
        ]);

        if ($data->tag_ids) {
            $listing->tags()->sync($data->tag_ids);
        }

        event(new MktListingCreated($listing));

        return $listing;
    }
}
