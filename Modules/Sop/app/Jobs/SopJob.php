<?php

namespace Modules\Sop\Jobs;

use App\Foundation\Jobs\TenantAwareJob;

/**
 * Base job for all tenant-scoped SOP queue jobs.
 *
 * Extends TenantAwareJob to capture organization_id at dispatch time
 * and restore TenantContext in the queue worker via $this->withTenant().
 *
 * Usage in subclass:
 *   public function handle(): void
 *   {
 *       $this->withTenant(function () {
 *           // logic here — TenantContext is set
 *       });
 *   }
 */
abstract class SopJob extends TenantAwareJob {}
