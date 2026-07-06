<?php

namespace Modules\Customer\Data;

use Spatie\LaravelData\Data;

class CustomerActivityData extends Data
{
    public function __construct(
        public int     $customerId,
        public int     $orgId,
        public int     $type,
        public string  $title,
        public ?string $description     = null,
        public ?string $outcome         = null,
        public ?string $scheduledAt     = null,
        public ?string $completedAt     = null,
        public ?int    $durationMinutes = null,
        public ?int    $actorId         = null,
        public ?string $actorName       = null,
        public ?int    $leadId          = null,
    ) {}
}
