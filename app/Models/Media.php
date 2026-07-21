<?php

namespace App\Models;

use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
     * Override newQuery to bypass OrganizationScope when TenantContext is not set,
     * and to additionally surface platform-wide media (organization_id IS NULL) —
     * e.g. Post/Ocop/Banner content, see spec/Media_Library_Technical_Specification.md §5.1/§7.1 —
     * alongside the current tenant's own media, without ever loosening OrganizationScope
     * itself (that scope is shared by every other tenant-scoped model in the app).
     */
    public function newQuery(): Builder
    {
        $query = parent::newQuery()->withoutGlobalScope(OrganizationScope::class);

        // Super-admin sees every organization's media, same exemption OrganizationScope
        // itself grants for every other tenant-scoped model — preserved here since we
        // bypass that scope entirely instead of letting it run.
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            return $query;
        }

        if (! TenantContext::isSet()) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            $q->where('organization_id', TenantContext::getOrganizationId())
              ->orWhereNull('organization_id');
        });
    }

    /**
     * True if the given target model (FQCN or instance) is tenant-scoped —
     * decides whether MediaUploadService::upload() should stamp organization_id.
     * Platform-wide models (Post/Ocop/Banner) never use BelongsToOrganization.
     */
    public static function targetIsTenantScoped(string|Model $modelOrClass): bool
    {
        $class = is_string($modelOrClass) ? $modelOrClass : get_class($modelOrClass);

        return in_array(BelongsToOrganization::class, class_uses_recursive($class), true);
    }
}
