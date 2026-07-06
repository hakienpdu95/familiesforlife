<?php

namespace Modules\Lead\Data;

use Spatie\LaravelData\Data;

class LeadActivityData extends Data
{
    public function __construct(
        public int     $leadId,
        public int     $orgId,
        public int     $type,
        public string  $title,
        public ?string $description     = null,
        public ?string $outcome         = null,
        public ?string $scheduledAt     = null,
        public ?string $completedAt     = null,
        public ?int    $durationMinutes = null,
        public ?int    $attendeeCount   = null,
        public ?int    $actorId         = null,
        public ?string $actorName       = null,
    ) {}
}
