<?php

namespace Modules\LeadSource\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SourceDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int  $sourceId,
        public readonly ?int $organizationId,
    ) {}
}
