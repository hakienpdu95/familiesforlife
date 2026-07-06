<?php

namespace Modules\Assessment\Engine;

readonly class RoadmapPhaseResult
{
    /**
     * @param string[] $milestones
     */
    public function __construct(
        public readonly string  $phaseCode,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly ?int    $durationWeeks,
        public readonly array   $milestones,
    ) {}

    public function toArray(): array
    {
        return [
            'phase_code'     => $this->phaseCode,
            'title'          => $this->title,
            'description'    => $this->description,
            'duration_weeks' => $this->durationWeeks,
            'milestones'     => $this->milestones,
        ];
    }
}
