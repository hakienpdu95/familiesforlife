<?php

namespace Modules\Assessment\Engine;

readonly class ClassificationResult
{
    public function __construct(
        public readonly string  $classificationType,  // none | score_band | pass_fail | persona_match
        public readonly ?string $bandCode,
        public readonly ?bool   $passed,
        public readonly ?string $personaCode,
        public readonly ?float  $matchScore,
        public readonly ?string $label = null,
    ) {}

    public static function none(): self
    {
        return new self('none', null, null, null, null);
    }

    public static function scoreBand(string $bandCode, string $label): self
    {
        return new self('score_band', $bandCode, null, null, null, $label);
    }

    public static function passFail(bool $passed, string $label): self
    {
        return new self('pass_fail', null, $passed, null, null, $label);
    }

    public static function persona(string $personaCode, float $matchScore, string $label): self
    {
        return new self('persona_match', null, null, $personaCode, $matchScore, $label);
    }
}
