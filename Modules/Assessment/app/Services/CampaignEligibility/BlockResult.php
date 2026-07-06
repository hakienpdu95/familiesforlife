<?php

namespace Modules\Assessment\Services\CampaignEligibility;

readonly class BlockResult
{
    public function __construct(
        public string  $message,
        public ?string $actionUrl   = null,
        public ?string $actionLabel = null,
    ) {}
}
