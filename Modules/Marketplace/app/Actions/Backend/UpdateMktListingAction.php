<?php

namespace Modules\Marketplace\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\StoreMktListingData;
use Modules\Marketplace\Events\MktListingUpdated;
use Modules\Marketplace\Models\MktListing;

class UpdateMktListingAction
{
    use AsAction;

    public function handle(MktListing $listing, StoreMktListingData $data): MktListing
    {
        $listing->update([
            'listing_type'        => $data->listing_type->value,
            'title'               => $data->title,
            'description'         => $data->description,
            'requirements'        => $data->requirements,
            'benefits'            => $data->benefits,
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

        if ($data->tag_ids !== null) {
            $listing->tags()->sync($data->tag_ids);
        }

        event(new MktListingUpdated($listing));

        return $listing->fresh();
    }
}
