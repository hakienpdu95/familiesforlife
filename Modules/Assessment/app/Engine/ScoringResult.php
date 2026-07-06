<?php

namespace Modules\Assessment\Engine;

readonly class ScoringResult
{
    /**
     * @param  array<string, DomainScoreResult>  $domainScores   keyed by domain_code
     * @param  array<string, DomainScoreResult>  $sectionScores  keyed by section_code
     * @param  array<string, bool>               $signalFlags    keyed by flag_code
     * @param  string[]                          $painPoints     list of pain_point_code
     * @param  RecommendationResult[]            $recommendations ordered by priority
     * @param  RoadmapPhaseResult[]              $roadmap        ordered by sort_order
     * @param  array<string, array>              $questionScores keyed by question_code
     */
    public function __construct(
        public readonly ?float             $overallScore,
        public readonly string             $assessmentCode,
        public readonly ClassificationResult $classification,
        public readonly array              $domainScores,
        public readonly array              $sectionScores,
        public readonly array              $signalFlags,
        public readonly array              $painPoints,
        public readonly array              $recommendations,
        public readonly array              $roadmap,
        public readonly int                $weightVersion,
        public readonly array              $questionScores = [],
    ) {}

    /** @deprecated Dùng classification->bandCode thay thế */
    public function getMaturityLevel(): ?string
    {
        return $this->classification->bandCode ?? $this->classification->personaCode;
    }

    public function toArray(): array
    {
        return [
            'overall_score'   => $this->overallScore !== null ? round($this->overallScore, 2) : null,
            'weight_version'  => $this->weightVersion,
            'assessment_code' => $this->assessmentCode,
            'classification'  => [
                'type'         => $this->classification->classificationType,
                'band_code'    => $this->classification->bandCode,
                'passed'       => $this->classification->passed,
                'persona_code' => $this->classification->personaCode,
                'match_score'  => $this->classification->matchScore,
                'label'        => $this->classification->label,
            ],
            'domain_scores'   => array_map(fn (DomainScoreResult $d) => $d->toArray(), $this->domainScores),
            'section_scores'  => array_map(fn (DomainScoreResult $d) => $d->toArray(), $this->sectionScores),
            'signal_flags'    => $this->signalFlags,
            'pain_points'     => $this->painPoints,
            'recommendations' => array_map(fn (RecommendationResult $r) => $r->toArray(), $this->recommendations),
            'roadmap'         => array_map(fn (RoadmapPhaseResult $p) => $p->toArray(), $this->roadmap),
        ];
    }
}
