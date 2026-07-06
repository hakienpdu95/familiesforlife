<?php

namespace Modules\Assessment\Engine;

readonly class AggregatedResult
{
    /**
     * @param  array<string, DomainScoreResult>  $domainScores  keyed by domain_code
     * @param  array<string, DomainScoreResult>  $sectionScores keyed by section_code (sectioned mode)
     * @param  float|null  $overallScore  NULL nếu sectioned/none
     */
    public function __construct(
        public readonly array $domainScores,
        public readonly array $sectionScores,
        public readonly ?float $overallScore,
    ) {}
}
