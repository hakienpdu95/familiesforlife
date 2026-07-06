<?php

namespace Modules\Assessment\Engine;

use Modules\Assessment\Models\RoadmapPhase;

class RoadmapLoader
{
    /** @return RoadmapPhaseResult[] */
    public function load(string $assessmentCode, ClassificationResult $classification): array
    {
        $bandCode = $classification->bandCode ?? $classification->personaCode;

        if ($bandCode === null) {
            return [];
        }

        $phases = RoadmapPhase::forBand($assessmentCode, $bandCode)
            ->with('milestones')
            ->get();

        return $phases->map(fn (RoadmapPhase $phase) => new RoadmapPhaseResult(
            phaseCode:     $phase->phase_code,
            title:         $phase->title,
            description:   $phase->description,
            durationWeeks: $phase->duration_weeks,
            milestones:    $phase->milestones->pluck('title')->all(),
        ))->all();
    }
}
