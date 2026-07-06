<?php

namespace App\Models;

use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * Extends Spatie's Media model with multi-tenant isolation.
 *
 * IMPORTANT: extends SpatieMedia directly — NOT TenantAwareModel.
 * Reason: TenantAwareModel pulls in SoftDeletes which breaks Spatie's
 * hard-delete assumptions and schema (no deleted_at column on media table).
 *
 * OrganizationScope failsafe (WHERE 0=1 when context null) is bypassed
 * in newQuery() so Spatie's internal library operations (path generation,
 * conversion tracking) always work regardless of HTTP context.
 */
class Media extends SpatieMedia
{
    use BelongsToOrganization;

    /**
     * Override newQuery to bypass OrganizationScope when TenantContext is not set.
     * This allows Spatie's internal queries to run in artisan/cron contexts while
     * still enforcing tenant isolation during HTTP requests.
     */
    public function newQuery(): Builder
    {
        $query = parent::newQuery();

        if (! TenantContext::isSet()) {
            $query->withoutGlobalScope(OrganizationScope::class);
        }

        return $query;
    }
}
