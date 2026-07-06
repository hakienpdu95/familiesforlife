<?php

namespace Modules\Marketplace\Actions\Backend;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\RegisterEmployerData;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\ListingType;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Enums\WorkType;
use Modules\Marketplace\Enums\ExperienceLevel;
use Modules\Marketplace\Enums\ListingVisibility;
use Modules\Marketplace\Models\MktListing;

class RegisterEmployerAction
{
    use AsAction;

    public function handle(RegisterEmployerData $data): array
    {
        return DB::transaction(function () use ($data): array {
            // 1. Create organization (pending)
            $org = Organization::create([
                'name'   => $data->company_name,
                'email'  => $data->hr_email,
                'status' => 'pending',
                'source' => 'marketplace_signup',
                'website' => $data->website,
            ]);

            // 2. Create org_admin user
            $user = User::create([
                'name'            => $data->contact_name,
                'email'           => $data->hr_email,
                'password'        => Hash::make($data->password),
                'organization_id' => $org->id,
            ]);
            $user->assignRole('org_admin');

            // 3. Create initial listing (pending_review)
            $listing = null;
            if ($data->listing_title) {
                $listing = MktListing::create([
                    'org_id'       => $org->id,
                    'posted_by'    => $user->id,
                    'poster_type'  => PosterType::PENDING_ORG->value,
                    'listing_type' => ListingType::JOB->value,
                    'title'        => $data->listing_title,
                    'description'  => $data->listing_description ?? '',
                    'status'       => ListingStatus::PENDING_REVIEW->value,
                    'visibility'   => ListingVisibility::PUBLIC->value,
                    'work_type'    => WorkType::FLEXIBLE->value,
                    'experience_level' => ExperienceLevel::ANY->value,
                    'location'     => $data->listing_location,
                ]);
            }

            return compact('org', 'user', 'listing');
        });
    }
}
