<?php

namespace Modules\Assessment\Contracts;

interface ScoringSubjectInterface
{
    public function getScoringSubjectId(): int;

    public function getScoringSubjectType(): string;

    public function getAssessmentCode(): string;

    /**
     * @return array<string, array{type: string, value?: mixed, values?: array}>
     */
    public function getScoringAnswers(): array;
}
