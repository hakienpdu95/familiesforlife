<?php

namespace Modules\Organization\Traits;

/**
 * Thin re-export of the application-level BelongsToOrganization trait.
 *
 * Module code should use this namespace so that the module remains
 * self-contained, while still using the shared underlying implementation.
 */
trait BelongsToOrganization
{
    use \App\Shared\Tenancy\Traits\BelongsToOrganization;
}
