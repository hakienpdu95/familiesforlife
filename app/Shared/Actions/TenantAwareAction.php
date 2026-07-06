<?php

namespace App\Shared\Actions;

use App\Foundation\Exceptions\TenantNotSetException;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Base action for all tenant-scoped use cases.
 *
 * Integrates lorisleiva/laravel-actions (AsAction) with TenantContext.
 * Use for actions that must always operate within an organization.
 *
 * Subclasses gain:
 *   - AsAction: run(), dispatch(), makeJob(), asController(), asListener()
 *   - organization() / organizationId(): current tenant helpers
 *
 * Usage:
 *   class CreateLead extends TenantAwareAction
 *   {
 *       public function handle(CreateLeadData $data): Lead { ... }
 *   }
 *
 *   CreateLead::run($data);           // sync
 *   CreateLead::dispatch($data);      // queued
 */
abstract class TenantAwareAction
{
    use AsAction;

    /** @throws TenantNotSetException */
    protected function organization(): Organization
    {
        return TenantContext::resolve();
    }

    /** @throws TenantNotSetException */
    protected function organizationId(): int
    {
        return TenantContext::resolve()->id;
    }

    protected function hasTenant(): bool
    {
        return TenantContext::isSet();
    }
}
