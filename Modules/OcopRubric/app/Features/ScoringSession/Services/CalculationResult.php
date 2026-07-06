<?php

namespace Modules\OcopRubric\Features\ScoringSession\Services;

class CalculationResult
{
    /** @param array<string,float> $sectionScores keyed theo mã Phần A/B/C */
    public function __construct(
        public readonly array $sectionScores,
        public readonly float $totalScore,
        public readonly ?int $starRank,
        public readonly bool $isCertifiable,
    ) {}
}
