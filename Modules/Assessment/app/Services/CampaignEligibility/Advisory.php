<?php

namespace Modules\Assessment\Services\CampaignEligibility;

readonly class Advisory
{
    public function __construct(
        public string  $severity,      // 'info' | 'warning'
        public string  $message,
        public ?string $actionUrl   = null,
        public ?string $actionLabel = null,
    ) {}
}
