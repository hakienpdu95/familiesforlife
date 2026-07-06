<?php

namespace Modules\Organization\Actions;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Data\Requests\CreateOrganizationData;
use Modules\Organization\Events\OrganizationCreated;
use Modules\Organization\Models\Organization;
use Modules\Organization\Models\OrganizationMember;
use Spatie\Permission\PermissionRegistrar;

/**
 * Create a new Organization and assign the given user as owner.
 *
 * Usage: CreateOrganizationAction::run($data, $owner)
 */
class CreateOrganizationAction
{
    use AsAction;

    public function handle(CreateOrganizationData $data, User $owner): Organization
    {
        return DB::transaction(function () use ($data, $owner): Organization {
            // 1. Create the organization
            $organization = Organization::create([
                'name'          => $data->name,
                'status'        => 'active',
                'owner_id'      => $owner->id,
                'tax_code'      => $data->tax_code,
                'phone'         => $data->phone,
                'email'         => $data->email,
                'website'       => $data->website,
                'industry'      => $data->industry,
                'address'       => $data->address,
                'city'          => $data->city,
                'country'       => $data->country,
                'province_code' => $data->province_code,
                'ward_code'     => $data->ward_code,
                'full_address'  => $data->full_address,
                'description'   => $data->description,
            ]);

            // 2. Create the owner membership record
            OrganizationMember::create([
                'organization_id' => $organization->id,
                'user_id'         => $owner->id,
                'role'            => OrganizationMember::ROLE_OWNER,
                'joined_at'       => now(),
            ]);

            // 3. Set Spatie Teams context for this organization
            setPermissionsTeamId($organization->id);

            // 4. Assign Spatie 'owner' role scoped to this organization
            $owner->assignRole('owner');

            // 5. Update TenantContext
            TenantContext::set($organization);

            // 6. Fire event (listener handles activity logging)
            event(new OrganizationCreated($organization));

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $organization;
        });
    }
}
