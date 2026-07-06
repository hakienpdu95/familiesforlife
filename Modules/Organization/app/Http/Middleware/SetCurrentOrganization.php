<?php

namespace Modules\Organization\Http\Middleware;

use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Organization\Models\Organization;
use Modules\Organization\Models\OrganizationMember;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve and set the current organization for authenticated users.
 *
 * Resolution order:
 *  1. Auth user's organization_id (single-org user)
 *  2. session('current_organization_id') (user switched org)
 *  3. First org the user is a member of (fallback)
 *
 * For super-admin users (organization_id = null), skip team setting.
 */
class SetCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only run when authenticated
        if ($user === null) {
            return $next($request);
        }

        // Super-admin: no organization scope, skip
        if ($user->organization_id === null && ! $user->hasRole('super-admin')) {
            return $next($request);
        }

        $organization = $this->resolveOrganization($request, $user);

        if ($organization !== null) {
            TenantContext::set($organization);
            setPermissionsTeamId($organization->id);

            // Make the resolved org available to controllers via request
            $request->merge(['_current_organization' => $organization]);
        } else {
            // Super-admin with no org context — clear team scope
            setPermissionsTeamId(null);
        }

        return $next($request);
    }

    private function resolveOrganization(Request $request, $user): ?Organization
    {
        // 1. Session-stored switch (user explicitly switched)
        $sessionOrgId = session('current_organization_id');
        if ($sessionOrgId) {
            // Verify user is still a member
            $isMember = OrganizationMember::where('organization_id', $sessionOrgId)
                ->where('user_id', $user->id)
                ->exists();

            if ($isMember) {
                return Organization::find($sessionOrgId);
            }

            // Session org no longer valid — clear it
            session()->forget('current_organization_id');
        }

        // 2. User's direct organization_id
        if ($user->organization_id) {
            return Organization::find($user->organization_id);
        }

        // 3. Most recently joined organization the user is a member of
        $membership = OrganizationMember::where('user_id', $user->id)
            ->latest('joined_at')
            ->first();
        if ($membership) {
            return Organization::find($membership->organization_id);
        }

        return null;
    }
}
