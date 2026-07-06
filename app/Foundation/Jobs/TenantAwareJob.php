<?php

namespace App\Foundation\Jobs;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Base job for all tenant-scoped queue jobs.
 *
 * Captures the current organization_id at dispatch time and restores
 * TenantContext when the job runs on the queue worker (which has no HTTP context).
 *
 * Usage in subclass:
 *   public function handle(): void
 *   {
 *       $this->withTenant(function () {
 *           // your job logic — TenantContext is set here
 *       });
 *   }
 */
abstract class TenantAwareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public readonly ?int $organizationId;

    public function __construct()
    {
        $this->organizationId = TenantContext::getOrganizationId();
    }

    /**
     * Run $callback with the organization's tenant context restored.
     * Always flushes back to the caller's state when done.
     */
    protected function withTenant(callable $callback): mixed
    {
        if ($this->organizationId === null) {
            return $callback();
        }

        $org = Organization::find($this->organizationId);

        if ($org === null) {
            return null;
        }

        return TenantContext::runForOrganization($org, $callback);
    }
}
