<?php

namespace Modules\Assessment\Engine;

readonly class DomainScoreResult
{
    public function __construct(
        public readonly string $domainCode,
        public readonly int    $rawScore,
        public readonly float  $normalizedScore,
    ) {}

    public function toArray(): array
    {
        return [
            'domain_code'      => $this->domainCode,
            'raw'              => $this->rawScore,
            'normalized'       => round($this->normalizedScore, 2),
        ];
    }
}
