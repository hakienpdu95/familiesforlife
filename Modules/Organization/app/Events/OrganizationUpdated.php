<?php

namespace Modules\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Organization\Models\Organization;

class OrganizationUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Organization $organization,
    ) {}
}
