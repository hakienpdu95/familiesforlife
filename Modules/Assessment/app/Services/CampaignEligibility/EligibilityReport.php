<?php

namespace Modules\Assessment\Services\CampaignEligibility;

readonly class EligibilityReport
{
    /** @param Advisory[] $advisories */
    private function __construct(
        public bool         $canJoin,
        public ?BlockResult $block,
        public array        $advisories,
    ) {}

    public static function blocked(BlockResult $block): self
    {
        return new self(false, $block, []);
    }

    /** @param Advisory[] $advisories */
    public static function allowed(array $advisories = []): self
    {
        return new self(true, null, $advisories);
    }
}
