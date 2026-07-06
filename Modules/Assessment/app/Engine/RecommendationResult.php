<?php

namespace Modules\Assessment\Engine;

readonly class RecommendationResult
{
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly ?string $description,
        public readonly int    $priority,
    ) {}

    public function toArray(): array
    {
        return [
            'code'        => $this->code,
            'label'       => $this->label,
            'description' => $this->description,
            'priority'    => $this->priority,
        ];
    }
}
