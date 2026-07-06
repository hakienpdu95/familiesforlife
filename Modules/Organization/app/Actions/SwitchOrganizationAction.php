<?php

namespace Modules\Organization\Actions;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Models\Organization;
use Modules\Organization\Models\OrganizationMember;

/**
 * Switch the active organization for the current session.
 *
 * Verifies the user is a member before switching.
 *
 * Usage: SwitchOrganizationAction::run($org, $user)
 */
class SwitchOrganizationAction
{
    use AsAction;

    public function handle(Organization $org, User $user): void
    {
        // Verify user is a member of the target organization
        $isMember = OrganizationMember::where('organization_id', $org->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'organization' => 'You are not a member of this organization.',
            ]);
        }

        // Store in session
        session(['current_organization_id' => $org->id]);

        // Update tenant context for this request
        TenantContext::set($org);

        // Update Spatie Teams scope
        setPermissionsTeamId($org->id);
    }
}
